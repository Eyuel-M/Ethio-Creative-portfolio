<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:seed', function () {
    if (\App\Support\DemoData::present()) {
        $this->warn('Demo data is already loaded. Run `php artisan demo:clear` first to reload it.');

        return 1;
    }
    $this->info('Adding placeholder creatives, portfolios, jobs, projects and reviews…');
    $this->call('db:seed', ['--class' => \Database\Seeders\DemoSeeder::class, '--force' => true]);
    $this->info('Done. Demo logins (password "password"): freelancer@demo.test, client@demo.test, admin@demo.test');
    $this->line('Remove it all later with: php artisan demo:clear');
})->purpose('Load removable placeholder content (accounts on @demo.test)');

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
