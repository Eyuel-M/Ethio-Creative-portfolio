<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('freelancer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('job_post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->unique(['client_user_id', 'freelancer_user_id', 'job_post_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        // Immutable agreement versions. A change creates a new version needing fresh confirmations.
        Schema::create('agreement_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->text('scope');
            $table->text('deliverables');
            $table->unsignedBigInteger('amount_minor'); // record only — platform never collects it
            $table->string('currency', 3)->default('ETB');
            $table->date('deadline');
            $table->unsignedTinyInteger('revisions')->default(2);
            $table->text('cancellation_terms')->nullable();
            $table->json('milestones')->nullable();
            $table->string('status', 30)->default('awaiting_confirmations'); // awaiting_confirmations, confirmed, superseded
            $table->string('content_hash', 64);
            $table->timestamps();
            $table->unique(['application_id', 'version']);
        });

        Schema::create('agreement_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('agreement_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('content_hash', 64);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('confirmed_at');
            $table->unique(['agreement_version_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUlid('agreement_version_id')->constrained();
            $table->foreignId('client_user_id')->constrained('users');
            $table->foreignId('freelancer_user_id')->constrained('users');
            $table->string('title');
            $table->string('status', 30)->default('in_progress');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignUlid('project_id')->nullable()->after('invitation_id')->constrained()->nullOnDelete();
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('amount_minor')->nullable();
            $table->string('status', 30)->default('not_started');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('project_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploader_id')->constrained('users');
            $table->string('disk', 20);
            $table->string('object_key')->unique();
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('status', 20)->default('available'); // available, receipt_confirmed, problem_reported, preserved, deleted
            $table->timestamp('receipt_confirmed_at')->nullable();
            $table->timestamp('delete_after')->nullable();
            $table->boolean('preservation_hold')->default(false);
            $table->timestamps();
        });

        Schema::create('file_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('project_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30); // uploaded, download_authorized, receipt_confirmed, problem_reported, deleted
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->foreignId('reviewee_id')->constrained('users');
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        foreach (['reviews', 'file_events', 'project_files', 'project_events', 'milestones'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('conversations', fn (Blueprint $t) => $t->dropConstrainedForeignId('project_id'));
        foreach (['projects', 'agreement_confirmations', 'agreement_versions', 'messages', 'conversations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
