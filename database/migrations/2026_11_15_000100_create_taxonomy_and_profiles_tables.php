<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 40)->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('location')->nullable();
            $table->string('availability', 20)->default('available'); // available, busy, unavailable
            $table->string('experience_level', 20)->default('entry'); // entry, intermediate, expert
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->unsignedInteger('hourly_rate_minor')->nullable();
            $table->string('currency', 3)->default('ETB');
            $table->json('languages')->nullable();
            $table->json('tools')->nullable();
            $table->string('website')->nullable();
            $table->string('cover_path')->nullable();
            // Private contact details — never rendered publicly; revealed only after mutual agreement confirmation.
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_public')->default(true);
            $table->string('plan', 20)->default('free'); // free, professional
            $table->timestamp('featured_until')->nullable();
            $table->unsignedInteger('profile_views')->default(0);
            $table->timestamps();
            $table->index(['category_id', 'is_public']);
        });

        Schema::create('freelancer_profile_skill', function (Blueprint $table) {
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['freelancer_profile_id', 'skill_id']);
        });

        Schema::create('freelancer_payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('encrypted_cbe_account_number')->nullable();
            $table->text('encrypted_telebirr_number')->nullable();
            $table->string('preferred_method', 20)->nullable(); // cbe, telebirr
            $table->string('status', 20)->default('not_provided'); // not_provided, partial, complete
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('individual'); // individual, company
            $table->string('company_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
        Schema::dropIfExists('freelancer_payment_details');
        Schema::dropIfExists('freelancer_profile_skill');
        Schema::dropIfExists('freelancer_profiles');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('categories');
    }
};
