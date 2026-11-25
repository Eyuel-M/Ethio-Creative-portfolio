<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Insights: studies, guides, product updates and stories published by the team, built from blocks. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->string('excerpt', 400)->nullable();
            $table->string('type', 20)->default('article');          // article | study | guide | update | spotlight
            $table->string('audience', 20)->default('everyone');     // everyone | freelancers | clients
            $table->json('topics')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_alt', 200)->nullable();
            $table->string('cover_caption', 200)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_label', 120)->nullable();         // byline override, e.g. "DezignersHub Research"
            $table->string('status', 20)->default('draft');          // draft | published | archived (future published_at = scheduled)
            $table->timestamp('published_at')->nullable();
            $table->timestamp('featured_at')->nullable();            // pinned to the top of Insights
            $table->json('settings')->nullable();                     // layout, cover style, accent, toggles
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 180)->nullable();
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->unsignedInteger('views')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index('type');
        });

        Schema::create('post_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('post_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title', 300)->nullable();
            $table->text('body')->nullable();
            $table->string('caption', 300)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('alt', 200)->nullable();
            $table->string('media_path')->nullable();
            $table->json('media')->nullable();   // gallery images
            $table->json('items')->nullable();   // stats, FAQ
            $table->json('settings')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['post_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_blocks');
        Schema::dropIfExists('posts');
    }
};
