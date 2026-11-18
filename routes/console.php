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
