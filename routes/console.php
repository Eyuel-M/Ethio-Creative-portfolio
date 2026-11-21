<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:seed {--fresh : Remove existing placeholder content first}', function () {
    if ($this->option('fresh') && \App\Support\DemoData::present()) {
        $this->info('Removing existing placeholder content…');
        \App\Support\DemoData::clear();
    }
    if (\App\Support\DemoData::present() && \App\Support\DemoData::complete()) {
        $this->warn('Demo data is already loaded. Use `php artisan demo:seed --fresh` to reload it.');

        return 1;
    }
    $this->info('Adding placeholder creatives, portfolios, jobs, projects and reviews… (this takes a few seconds)');
    try {
        $this->callSilently('db:seed', ['--class' => \Database\Seeders\DemoSeeder::class, '--force' => true]);
    } catch (\Throwable $e) {
        $this->error('Loading placeholder content failed — nothing was saved.');
        $this->line($e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().')');

        return 1;
    }
    if (! \App\Support\DemoData::complete()) {
        $this->error('Placeholder content did not load completely. Run `php artisan demo:status` and share the output.');

        return 1;
    }
    $this->table(['', 'Count'], collect(\App\Support\DemoData::status())->map(fn ($v, $k) => [$k, $v])->values()->all());
    $this->info('Done. Demo logins (password "password"): freelancer@demo.test, client@demo.test, admin@demo.test');
    $this->line('Remove it all later with: php artisan demo:clear');
})->purpose('Load removable placeholder content (accounts on @demo.test)');

Artisan::command('demo:status', function () {
    $this->table(['', 'Count'], collect(\App\Support\DemoData::status())->map(fn ($v, $k) => [$k, $v])->values()->all());
})->purpose('Show what placeholder and real content is in the database');

Artisan::command('demo:clear {--force : Skip the confirmation}', function () {
    if (! \App\Support\DemoData::present()) {
        $this->info('No demo data found.');

        return 0;
    }
    $n = \App\Support\DemoData::users()->count();
    if (! $this->option('force') && ! $this->confirm("Delete {$n} placeholder accounts (@demo.test) and all their content? Your own accounts are kept.", true)) {
        return 1;
    }
    $counts = \App\Support\DemoData::clear();
    $this->info('Removed: '.collect($counts)->map(fn ($v, $k) => "$v $k")->implode(', ').'.');
})->purpose('Remove all placeholder content, keeping real accounts');

Artisan::command('media:prune-uploads', function () {
    $old = \App\Models\MediaUpload::where('created_at', '<', now()->subHours(\App\Models\MediaUpload::KEEP_HOURS))->get();
    foreach ($old as $u) {
        \App\Support\Media::delete($u->path);
        $u->delete();
    }
    $this->info("Removed {$old->count()} unused uploads.");
})->purpose('Delete images that were uploaded but never saved into a project');

\Illuminate\Support\Facades\Schedule::command('media:prune-uploads')->daily();

// Heartbeat for Admin → System health ("is the scheduler running?").
\Illuminate\Support\Facades\Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('scheduler:last_run', now()->toIso8601String(), now()->addDay()))->everyMinute()->name('scheduler-heartbeat');
// Expired personal-data exports are deleted after 7 days.
\Illuminate\Support\Facades\Schedule::call(function () {
    foreach (\App\Models\DataRequest::whereNotNull('export_path')->where('export_expires_at', '<', now())->get() as $r) {
        \Illuminate\Support\Facades\Storage::disk('local')->delete($r->export_path);
        $r->forceFill(['export_path' => null])->save();
    }
})->daily()->name('expire-data-exports');
