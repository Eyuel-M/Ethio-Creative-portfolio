<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use App\Models\Order;
use App\Models\PortfolioProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

/** One search box for the whole console: people, jobs, portfolio projects, orders and projects — by text or ID. */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $groups = strlen($q) >= 2 ? $this->find($q, 15) : [];

        return view('admin.search', compact('q', 'groups'));
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(collect($this->find($q, 4))->flatMap(fn ($items, $type) => collect($items)->map(fn ($i) => $i + ['type' => $type]))->take(12)->values());
    }

    /** @return array<string, list<array{title: string, sub: string, url: string}>> */
    private function find(string $q, int $limit): array
    {
        $me = auth()->user();
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $out = [];

        if ($me->canAdmin('users')) {
            $out['People'] = User::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('id', $q)
                ->orWhereHas('freelancerProfile', fn ($f) => $f->where('title', 'like', $like)->orWhere('contact_phone', 'like', $like))
                ->orWhereHas('clientProfile', fn ($c) => $c->where('company_name', 'like', $like)))
                ->limit($limit)->get()->map(fn ($u) => ['title' => $u->name, 'sub' => $u->email.' · '.ucfirst($u->status), 'url' => route('admin.users.show', $u)])->all();
        }
        if ($me->canAdmin('moderation') || $me->canAdmin('marketplace')) {
            $out['Jobs'] = JobPost::with('client')->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('id', $q))->latest()->limit($limit)->get()
                ->map(fn ($j) => ['title' => $j->title, 'sub' => $j->client?->displayName().' · '.ucfirst($j->status), 'url' => route('admin.jobs', ['q' => $j->id])])->all();
            $out['Portfolio'] = PortfolioProject::with('profile.user')->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('id', $q))->latest()->limit($limit)->get()
                ->map(fn ($p) => ['title' => $p->title, 'sub' => 'by '.$p->profile?->user?->name.' · '.ucfirst($p->status), 'url' => route('admin.portfolio', ['q' => $p->id])])->all();
        }
        if ($me->canAdmin('agreements')) {
            $out['Projects'] = Project::with(['client', 'freelancer'])->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('id', $q))->latest()->limit($limit)->get()
                ->map(fn ($p) => ['title' => $p->title, 'sub' => $p->client?->name.' ↔ '.$p->freelancer?->name, 'url' => route('admin.agreements.show', $p)])->all();
        }
        if ($me->canAdmin('finance')) {
            $out['Orders'] = Order::with(['user', 'product'])->where(fn ($w) => $w->where('id', $q)->orWhere('provider_reference', $q)->orWhereHas('user', fn ($u) => $u->where('email', 'like', $like)))
                ->latest()->limit($limit)->get()
                ->map(fn ($o) => ['title' => $o->product?->name.' · '.\App\Support\Money::format($o->amount_minor, $o->currency), 'sub' => $o->user?->email.' · '.ucfirst(str_replace('_', ' ', $o->status)), 'url' => route('admin.orders', ['q' => $o->id])])->all();
        }

        return array_filter($out);
    }
}
