<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataRequest;
use App\Notifications\MarketplaceNotification;
use App\Support\AdminMenu;
use App\Support\PersonalData;
use Illuminate\Http\Request;

/** Members' requests to download or delete their personal data (Ethiopia's Personal Data Protection Proclamation). */
class DataRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('status', 'pending');
        $requests = DataRequest::with(['user', 'handler'])->where('status', $tab)->orderBy('created_at', $tab === 'pending' ? 'asc' : 'desc')->paginate(20)->withQueryString();
        $counts = DataRequest::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        return view('admin.data-requests', compact('requests', 'tab', 'counts'));
    }

    public function process(Request $request, DataRequest $dataRequest)
    {
        abort_unless($dataRequest->status === 'pending', 422, 'Already handled.');
        $data = $request->validate(['decision' => 'required|in:complete,reject', 'reason' => 'required|string|min:3|max:300']);
        $user = $dataRequest->user;
        abort_if($data['decision'] === 'complete' && ! $user, 422, 'The account no longer exists.');
        abort_if($user?->isAdmin() && $dataRequest->type === 'deletion', 422, 'Remove admin access before deleting an admin account.');

        if ($data['decision'] === 'reject') {
            $dataRequest->forceFill(['status' => 'rejected', 'handled_by' => $request->user()->id, 'handled_at' => now(), 'decision_note' => $data['reason']])->save();
            $user?->notify(new MarketplaceNotification('About your data request', $data['reason'], route('settings'), 'shield'));
            AuditLog::record('data_request.rejected', $dataRequest, $data['reason']);
        } elseif ($dataRequest->type === 'export') {
            $path = PersonalData::export($user);
            $dataRequest->forceFill(['status' => 'completed', 'handled_by' => $request->user()->id, 'handled_at' => now(), 'decision_note' => $data['reason'], 'export_path' => $path, 'export_expires_at' => now()->addDays(7)])->save();
            $user->notify(new MarketplaceNotification('Your data export is ready', 'Download it from Settings within 7 days.', route('settings').'#your-data', 'download'));
            AuditLog::record('data_request.completed', $dataRequest, $data['reason'], ['type' => 'export']);
        } else {
            $dataRequest->forceFill(['status' => 'completed', 'handled_by' => $request->user()->id, 'handled_at' => now(), 'decision_note' => $data['reason']])->save();
            AuditLog::record('data_request.completed', $dataRequest, $data['reason'], ['type' => 'deletion', 'user' => $user->id]);
            PersonalData::erase($user);
        }
        AdminMenu::forget();

        return back()->with('status', 'Request handled and logged.');
    }
}
