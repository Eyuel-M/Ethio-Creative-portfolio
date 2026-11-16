<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform services only. No project-payment, escrow or contact-unlock products exist.
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique(); // job_post_standard, job_post_featured, freelancer_professional, freelancer_featured
            $table->string('audience', 20); // client, freelancer
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->unsignedBigInteger('price_minor');
            $table->string('currency', 3)->default('ETB');
            $table->unsignedSmallInteger('duration_days');
            $table->json('benefits')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignUlid('job_post_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('status', 20)->default('created'); // created, pending_provider, paid, failed, expired, refunded
            $table->string('provider', 30)->default('sandbox');
            $table->string('provider_reference')->nullable()->unique();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('provider_event_id')->unique();
            $table->foreignUlid('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignUlid('order_id')->unique()->constrained();
            $table->foreignUlid('job_post_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reportable_type');
            $table->string('reportable_id', 36);
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open'); // open, actioned, dismissed
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('target_type')->nullable();
            $table->string('target_id', 36)->nullable();
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'reports', 'entitlements', 'payment_events', 'orders', 'products'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
