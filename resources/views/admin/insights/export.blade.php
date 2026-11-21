<x-layouts.admin title="Data export">
<x-admin.header title="Data export" subtitle="Raw, pseudonymous data for your own analysis in Excel, Google Sheets, Python or a BI tool. No names, emails, phone numbers or payment details are included.">
    <x-slot:actions><x-admin.range :days="$days"/></x-slot:actions>
</x-admin.header>
<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    @foreach($datasets as $key => [$label, $desc])
        <div class="flex items-start gap-4 rounded-2xl border border-line bg-white p-5">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-zinc-100"><x-icon name="file" class="size-5 text-ink-soft"/></span>
            <div class="min-w-0 flex-1"><p class="font-semibold">{{ $label }}</p><p class="mt-1 text-sm text-muted">{{ $desc }}</p></div>
            <a href="{{ route('admin.insights.download', ['dataset' => $key, 'days' => $days]) }}" class="btn btn-sm btn-dark shrink-0"><x-icon name="download" class="size-4"/> CSV</a>
        </div>
    @endforeach
</div>
<x-admin.panel title="How the pieces join">
    <ul class="list-disc space-y-1.5 pl-5 text-sm text-ink-soft">
        <li><span class="tabular-nums text-xs">session_id</span> links page views and interactions to a visit; <span class="tabular-nums text-xs">member_id</span> links them to a member once they sign in.</li>
        <li><span class="tabular-nums text-xs">subject_type / subject_id</span> point to a portfolio project, profile or job — match them with the Portfolio and Jobs exports.</li>
        <li>Rows with <span class="tabular-nums text-xs">demo = 1</span> are placeholder data; filter them out for real analysis.</li>
        <li>Every download is recorded in the audit log.</li>
    </ul>
</x-admin.panel>
</x-layouts.admin>
