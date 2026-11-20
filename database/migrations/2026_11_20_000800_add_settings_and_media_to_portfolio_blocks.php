<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_blocks', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('embed_url');
            $table->json('media')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_blocks', function (Blueprint $table) {
            $table->dropColumn(['settings', 'media']);
        });
    }
};
