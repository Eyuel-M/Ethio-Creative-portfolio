<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\Product;
use App\Support\AdminMenu;
use App\Support\Insights;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Orders, revenue, refunds, subscriptions and the price list. The platform only sells its own services. */
class FinanceController extends Controller
{
    public function orders(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $tab = $request->query('status', '');
        $orders = Order::with(['user', 'product', 'jobPost'])
            ->when($tab, fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('id', $t)->orWhere('provider_reference', $t)->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$t}%"))))
            ->latest()->paginate(25)->withQueryString();
        $counts = Order::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        $from = Insights::since($days);
        $paid = Order::where('orders.status', 'paid')->where('orders.currency', 'ETB')->where('orders.paid_at', '>=', $from);
        $revenue = (int) (clone $paid)->sum('orders.amount_minor');
        $prev = (int) Order::where('status', 'paid')->where('currency', 'ETB')->whereBetween('paid_at', [$from->copy()->subDays($days), $from])->sum('amount_minor');
        $byProduct = (clone $paid)->join('products', 'products.id', '=', 'orders.product_id')
            ->selectRaw('products.name n, SUM(orders.amount_minor) s, COUNT(*) c')->groupBy('products.name')->orderByDesc('s')->get()
            ->map(fn ($r) => ['label' => $r->n, 'value' => (int) $r->s, 'extra' => $r->c.' orders'])->all();
        $daily = Insights::dailyRevenue($days);
        $refunded = (int) Order::where('status', 'refunded')->where('updated_at', '>=', $from)->sum('amount_minor');
        $failed = Order::where('status', 'failed')->where('created_at', '>=', $from)->count();
        $payers = (clone $paid)->distinct()->count('orders.user_id');

        return view('admin.finance.orders', compact('orders', 'tab', 'counts', 'days', 'revenue', 'prev', 'byProduct', 'daily', 'refunded', 'failed', 'payers'));
    }

    public function issues()
    {
        $orders = Order::with(['user', 'product'])->whereIn('status', ['failed', 'pending_provider'])->where('created_at', '>=', now()->subDays(14))->latest()->paginate(25);
        $events = DB::table('payment_events')->whereNull('processed_at')->latest()->take(20)->get();

        return view('admin.finance.issues', compact('orders', 'events'));
    }

    public function refund(Request $request, Order $order)
    {
        abort_unless($order->status === 'paid', 422, 'Only paid orders can be refunded.');
        $data = $request->validate(['reason' => 'required|string|min:3|max:300']);
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'refunded']);
            Entitlement::where('order_id', $order->id)->update(['ends_at' => now()]);
            if ($order->product?->code === 'freelancer_professional') {
                $stillPro = Entitlement::where('user_id', $order->user_id)->where('ends_at', '>', now())->whereHas('product', fn ($q) => $q->where('code', 'freelancer_professional'))->exists();
                if (! $stillPro) {
                    $order->user->freelancerProfile?->forceFill(['plan' => 'free'])->save();
                }
            }
        });
        AuditLog::record('order.refunded', $order, $data['reason'], ['amount' => $order->amount_minor]);
        AdminMenu::forget();

        return back()->with('status', 'Order marked refunded and access ended. Send the money back through '.ucfirst($order->provider).'.');
    }

    public function export(Request $request)
    {
        $days = (int) $request->query('days', 30);
        AuditLog::record('export.downloaded', null, 'Orders CSV', ['days' => $days]);
        $rows = Order::with(['user', 'product'])->where('created_at', '>=', Insights::since($days))->latest()->cursor();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['order_id', 'created_at', 'paid_at', 'status', 'product', 'amount', 'currency', 'provider', 'provider_reference', 'member_id', 'member_email']);
            foreach ($rows as $o) {
                fputcsv($out, [$o->id, $o->created_at, $o->paid_at, $o->status, $o->product?->code, Money::toMajor($o->amount_minor), $o->currency, $o->provider, $o->provider_reference, $o->user_id, $o->user?->email]);
            }
            fclose($out);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function subscriptions(Request $request)
    {
        $tab = $request->query('status', 'active');
        $items = Entitlement::with(['product', 'user'])
            ->tap(fn ($q) => match ($tab) {
                'expiring' => $q->whereBetween('ends_at', [now(), now()->addDays(7)]),
                'expired' => $q->where('ends_at', '<=', now()),
                default => $q->where('ends_at', '>', now()),
            })
            ->orderBy('ends_at', $tab === 'expired' ? 'desc' : 'asc')->paginate(25)->withQueryString();
        $byProduct = Entitlement::with('product')->where('ends_at', '>', now())->get()->groupBy(fn ($e) => $e->product?->name)->map->count();

        return view('admin.finance.subscriptions', compact('items', 'tab', 'byProduct'));
    }

    public function products()
    {
        $products = Product::orderBy('audience')->orderBy('sort_order')->get();
        $sales = Order::where('status', 'paid')->selectRaw('product_id, COUNT(*) c, SUM(amount_minor) s')->groupBy('product_id')->get()->keyBy('product_id');

        return view('admin.finance.products', compact('products', 'sales'));
    }

    public function updateProduct(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80', 'tagline' => 'nullable|string|max:160', 'price' => 'required|numeric|min:0|max:10000000',
            'duration_days' => 'required|integer|min:1|max:3650', 'is_active' => 'nullable|boolean', 'reason' => 'required|string|min:3|max:300',
        ]);
        $before = $product->only(['name', 'price_minor', 'duration_days', 'is_active']);
        $product->update(['name' => $data['name'], 'tagline' => $data['tagline'] ?? null, 'price_minor' => Money::toMinor($data['price']), 'duration_days' => $data['duration_days'], 'is_active' => $request->boolean('is_active')]);
        AuditLog::record('product.updated', $product, $data['reason'], ['before' => $before, 'after' => $product->only(['name', 'price_minor', 'duration_days', 'is_active'])]);

        return back()->with('status', "{$product->name} updated. New purchases use the new price; existing orders are unchanged.");
    }
}
