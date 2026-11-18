<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** `php artisan migrate --seed` loads the removable placeholder content (see DemoSeeder). */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
