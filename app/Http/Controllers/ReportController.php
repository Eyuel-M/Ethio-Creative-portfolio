<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:job,profile,portfolio,message',
            'id' => 'required|string|max:36',
            'reason' => 'required|in:spam,scam,inappropriate,copyright,impersonation,other',
            'details' => 'nullable|string|max:2000',
        ]);
        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $data['type'],
            'reportable_id' => $data['id'],
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);

        \App\Support\AdminMenu::forget();
        \App\Support\Analytics::track('report_filed', null, ['type' => $data['type']]);

        return back()->with('status', 'Thanks — our moderators will review this report.');
    }
}
