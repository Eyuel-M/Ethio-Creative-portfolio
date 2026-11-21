<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin operations (notes, settings, verification & data requests, moderation fields) and first-party analytics
 * (sessions, page views, interaction events). Analytics rows never store raw IPs; `is_demo` marks seeded rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('admin_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();     // the subject
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('client'); // client
            $table->string('business_name')->nullable();
            $table->string('registration_number', 80)->nullable();
            $table->string('website')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('requester_email');
            $table->string('type', 20); // export, deletion
            $table->string('status', 20)->default('pending'); // pending, completed, rejected
            $table->text('note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('decision_note', 300)->nullable();
            $table->string('export_path')->nullable();
            $table->timestamp('export_expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->timestamp('last_seen_at')->nullable()->after('last_login_at');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('assigned_to');
            $table->string('resolution_note', 300)->nullable()->after('resolved_at');
        });

        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('published_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->string('moderation_note', 300)->nullable()->after('reviewed_by');
            $table->timestamp('featured_at')->nullable()->after('moderation_note');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('body');
            $table->string('hidden_reason', 300)->nullable()->after('hidden_at');
        });

        // ── Analytics ──
        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('visitor_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at')->index();
            $table->timestamp('last_seen_at');
            $table->string('landing_path', 300);
            $table->string('exit_path', 300)->nullable();
            $table->string('source', 20)->index();           // search, social, direct, referral, email, campaign
            $table->string('source_detail', 80)->nullable(); // google, facebook, telegram, behance.net …
            $table->string('referrer_host', 120)->nullable();
            $table->string('utm_source', 80)->nullable();
            $table->string('utm_medium', 80)->nullable();
            $table->string('utm_campaign', 120)->nullable();
            $table->string('device', 10)->nullable();        // desktop, mobile, tablet
            $table->string('browser', 30)->nullable();
            $table->string('os', 30)->nullable();
            $table->string('country', 2)->nullable();
            $table->boolean('is_new_visitor')->default(true);
            $table->unsignedInteger('pageviews')->default(0);
            $table->unsignedInteger('events')->default(0);
            $table->unsignedInteger('engaged_seconds')->default(0);
            $table->unsignedTinyInteger('max_scroll')->default(0);
            $table->boolean('is_demo')->default(false)->index();
        });

        Schema::create('analytics_pageviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->index();
            $table->uuid('visitor_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path', 300);
            $table->string('route', 80)->nullable()->index();
            $table->string('subject_type', 30)->nullable(); // portfolio, profile, job
            $table->string('subject_id', 36)->nullable();
            $table->unsignedSmallInteger('position')->default(1); // nth page in the session
            $table->timestamp('viewed_at')->index();
            $table->unsignedInteger('engaged_seconds')->default(0);
            $table->unsignedTinyInteger('scroll_pct')->default(0);
            $table->boolean('is_demo')->default(false)->index();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->nullable()->index();
            $table->uuid('visitor_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 50)->index();
            $table->string('subject_type', 30)->nullable();
            $table->string('subject_id', 36)->nullable();
            $table->json('props')->nullable();
            $table->timestamp('created_at')->index();
            $table->boolean('is_demo')->default(false)->index();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['analytics_events', 'analytics_pageviews', 'analytics_sessions', 'data_requests', 'verification_requests', 'admin_notes', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('reviews', fn (Blueprint $t) => $t->dropColumn(['hidden_at', 'hidden_reason']));
        Schema::table('portfolio_projects', function (Blueprint $t) {
            $t->dropConstrainedForeignId('reviewed_by');
            $t->dropColumn(['reviewed_at', 'moderation_note', 'featured_at']);
        });
        Schema::table('reports', function (Blueprint $t) {
            $t->dropConstrainedForeignId('assigned_to');
            $t->dropColumn(['resolved_at', 'resolution_note']);
        });
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['last_login_at', 'last_seen_at']));
    }
};
