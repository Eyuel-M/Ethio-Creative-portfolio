<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Private bookmarks: projects a member saved for later. Unlike likes, nobody else sees these. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_saves', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['user_id', 'portfolio_project_id']);
            $table->index('portfolio_project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_saves');
    }
};
