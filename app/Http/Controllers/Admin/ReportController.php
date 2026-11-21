<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use App\Support\AdminMenu;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('status', 'open');
        $reports = Report::with(['reporter', 'assignee'])
            ->when($tab === 'mine', fn ($q) => $q->where('status', 'open')->where('assigned_to', $request->user()->id))
            ->when(in_array($tab, ['open', 'actioned', 'dismissed'], true), fn ($q) => $q->where('status', $tab))
            ->when($request->query('reason'), fn ($q, $r) => $q->where('reason', $r))
            ->orderByRaw($tab === 'open' || $tab === 'mine' ? 'created_at asc' : 'updated_at desc')->paginate(20)->withQueryString();
        $counts = Report::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');
        $mine = Report::where('status', 'open')->where('assigned_to', $request->user()->id)->count();

        return view('admin.reports', compact('reports', 'tab', 'counts', 'mine'));
    }

    public function assign(Request $request, Report $report)
    {
        $report->update(['assigned_to' => $report->assigned_to === $request->user()->id ? null : $request->user()->id]);
        AuditLog::record('report.assigned', $report);

        return back()->with('status', $report->assigned_to ? 'Assigned to you.' : 'Unassigned.');
    }

    public function resolve(Request $request, Report $report)
    {
        abort_unless($request->user()->canAdmin('moderation') || $request->user()->canAdmin('reports'), 403);
        $data = $request->validate(['status' => 'required|in:actioned,dismissed', 'reason' => 'required|string|min:3|max:300']);
        $report->update(['status' => $data['status'], 'resolved_at' => now(), 'resolution_note' => $data['reason'], 'assigned_to' => $report->assigned_to ?? $request->user()->id]);
        AuditLog::record('report.'.$data['status'], $report, $data['reason']);
        AdminMenu::forget();

        return back()->with('status', 'Report '.$data['status'].'.');
    }
}
