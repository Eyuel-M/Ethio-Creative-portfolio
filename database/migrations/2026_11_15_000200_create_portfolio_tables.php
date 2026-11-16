<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_alt')->nullable();
            $table->json('tags')->nullable();
            $table->string('client_attribution')->nullable();
            $table->date('project_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['freelancer_profile_id', 'status']);
        });

        Schema::create('portfolio_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // text, image, embed
            $table->text('body')->nullable();
            $table->string('media_path')->nullable();
            $table->string('caption')->nullable();
            $table->string('alt')->nullable();
            $table->string('embed_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_blocks');
        Schema::dropIfExists('portfolio_projects');
    }
};
