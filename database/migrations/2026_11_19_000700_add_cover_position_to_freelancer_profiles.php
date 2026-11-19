<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('freelancer_profiles', function (Blueprint $table) {
            // Vertical focus of the profile banner, 0 (top) – 100 (bottom); set by drag-to-position.
            $table->unsignedTinyInteger('cover_position')->default(50)->after('cover_path');
        });
    }

    public function down(): void
    {
        Schema::table('freelancer_profiles', fn (Blueprint $table) => $table->dropColumn('cover_position'));
    }
};
