<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\AuthController;
use App\Models\Application;
use App\Models\Category;
use App\Models\FreelancerPaymentDetails;
use App\Models\JobPost;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class MarketplaceTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutVite();
    }

    protected function freelancer(array $payment = ['cbe' => '1000123456789', 'telebirr' => '0911223344']): User
    {
        $u = User::factory()->create();
        AuthController::enableRole($u, 'freelancer');
        $u->refresh();
        $u->freelancerProfile->forceFill(['title' => 'Designer', 'contact_phone' => '+251900111222'])->save();
        $pd = new FreelancerPaymentDetails(['freelancer_profile_id' => $u->freelancerProfile->id]);
        $pd->encrypted_cbe_account_number = $payment['cbe'];
        $pd->encrypted_telebirr_number = $payment['telebirr'];
        $pd->preferred_method = 'cbe';
        $pd->refreshStatus();
        $pd->save();

        return $u->fresh();
    }

    protected function client(): User
    {
        $u = User::factory()->create();
        AuthController::enableRole($u, 'client');

        return $u->fresh();
    }

    protected function job(User $client, string $status = 'published'): JobPost
    {
        $cat = Category::firstOrCreate(['slug' => 'brand-identity'], ['name' => 'Brand Identity']);

        return JobPost::forceCreate([
            'id' => (string) Str::ulid(), 'client_profile_id' => $client->clientProfile->id, 'category_id' => $cat->id,
            'title' => 'Logo for a coffee brand', 'description' => str_repeat('A detailed description. ', 5),
            'budget_type' => 'fixed', 'budget_max_minor' => 1000000, 'currency' => 'ETB', 'experience_level' => 'entry',
            'status' => $status, 'published_at' => $status === 'published' ? now() : null, 'expires_at' => now()->addDays(30),
        ]);
    }

    protected function application(JobPost $job, User $freelancer, string $status = 'shortlisted'): Application
    {
        $a = new Application(['cover_message' => str_repeat('I would love to help. ', 3), 'proposed_amount_minor' => 900000, 'currency' => 'ETB', 'estimated_days' => 10]);
        $a->job_post_id = $job->id;
        $a->freelancer_profile_id = $freelancer->freelancerProfile->id;
        $a->status = $status;
        $a->save();

        return $a;
    }

    protected function terms(array $over = []): array
    {
        return array_merge(['title' => 'Logo project', 'scope' => 'Logo and colours', 'deliverables' => 'SVG, PNG', 'amount' => 9000, 'currency' => 'ETB', 'deadline' => now()->addWeeks(2)->format('Y-m-d'), 'revisions' => 2], $over);
    }

    protected function products(): void
    {
        Product::create(['code' => 'job_post_standard', 'audience' => 'client', 'name' => 'Standard job post', 'price_minor' => 150000, 'currency' => 'ETB', 'duration_days' => 30, 'benefits' => ['x']]);
        Product::create(['code' => 'freelancer_professional', 'audience' => 'freelancer', 'name' => 'Professional', 'price_minor' => 45000, 'currency' => 'ETB', 'duration_days' => 30, 'benefits' => ['x']]);
    }
}
