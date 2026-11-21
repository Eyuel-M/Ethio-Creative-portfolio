<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\VerificationRequest;
use App\Notifications\MarketplaceNotification;
use App\Support\AdminMenu;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('status', 'pending');
        $requests = VerificationRequest::with(['user.clientProfile', 'reviewer'])->where('status', $tab)
            ->orderBy($tab === 'pending' ? 'created_at' : 'reviewed_at', $tab === 'pending' ? 'asc' : 'desc')->paginate(20)->withQueryString();
        $counts = VerificationRequest::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        return view('admin.verification', compact('requests', 'tab', 'counts'));
    }

    public function decide(Request $request, VerificationRequest $verification)
    {
        abort_unless($verification->status === 'pending', 422, 'Already decided.');
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'reason' => 'required|string|min:3|max:300']);
        $verification->forceFill(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'decision_note' => $data['reason']])->save();
        $user = $verification->user;
        if ($data['decision'] === 'approved') {
            $user->clientProfile?->forceFill(['is_verified' => true])->save();
            $user->notify(new MarketplaceNotification('Your business is verified', 'A verified badge now appears on your jobs and profile.', route('settings'), 'verified'));
        } else {
            $user->notify(new MarketplaceNotification('Verification not approved yet', "We couldn't verify your business: {$data['reason']} You can send a new request from Settings.", route('settings'), 'flag'));
        }
        AuditLog::record('verification.'.$data['decision'], $user, $data['reason']);
        AdminMenu::forget();

        return back()->with('status', 'Verification '.$data['decision'].' and the client was notified.');
    }
}
