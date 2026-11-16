<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('client_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('deliverables')->nullable();
            $table->string('budget_type', 10)->default('fixed'); // fixed, hourly
            $table->unsignedBigInteger('budget_min_minor')->nullable();
            $table->unsignedBigInteger('budget_max_minor')->nullable();
            $table->string('currency', 3)->default('ETB');
            $table->string('duration', 40)->nullable();
            $table->date('deadline')->nullable();
            $table->string('experience_level', 20)->default('entry');
            $table->string('location')->nullable();
            $table->boolean('is_remote')->default(true);
            $table->unsignedTinyInteger('hires_needed')->default(1);
            $table->date('application_deadline')->nullable();
            $table->string('status', 20)->default('draft'); // draft, pending_payment, published, paused, filled, closed, expired, removed
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
        });

        Schema::create('job_post_skill', function (Blueprint $table) {
            $table->foreignUlid('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['job_post_id', 'skill_id']);
        });

        Schema::create('screening_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('job_post_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // multiple_choice, yes_no, multi_select, number, text, portfolio_link, file
            $table->string('prompt', 500);
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->text('cover_message');
            $table->unsignedBigInteger('proposed_amount_minor');
            $table->string('currency', 3)->default('ETB');
            $table->unsignedSmallInteger('estimated_days');
            $table->string('availability', 120)->nullable();
            $table->string('status', 20)->default('submitted'); // submitted, viewed, shortlisted, discussion, negotiation, accepted, rejected, withdrawn
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            $table->unique(['job_post_id', 'freelancer_profile_id']);
            $table->index(['job_post_id', 'status']);
        });

        Schema::create('application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screening_question_id')->constrained()->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->unique(['application_id', 'screening_question_id']);
        });

        Schema::create('application_portfolio_project', function (Blueprint $table) {
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->primary(['application_id', 'portfolio_project_id']);
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->date('respond_by')->nullable();
            $table->string('status', 20)->default('pending'); // pending, accepted, declined, withdrawn, expired
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['job_post_id', 'freelancer_profile_id']);
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('job_post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'job_post_id']);
        });

        Schema::create('shortlists', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->primary(['user_id', 'freelancer_profile_id']);
        });

        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->string('subject_id', 36);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['status_histories', 'shortlists', 'saved_jobs', 'invitations', 'application_portfolio_project', 'application_answers', 'applications', 'screening_questions', 'job_post_skill', 'job_posts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
