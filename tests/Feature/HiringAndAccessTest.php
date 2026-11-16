<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\PortfolioProject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HiringAndAccessTest extends MarketplaceTestCase
{
    public function test_visitors_see_only_published_jobs(): void
    {
        $c = $this->client();
        $live = $this->job($c);
        $draft = $this->job($c, 'draft');
        $this->get(route('jobs.index'))->assertOk()->assertSee($live->title);
        $this->get(route('jobs.show', $live))->assertOk();
        $this->get(route('jobs.show', $draft))->assertNotFound();
    }

    public function test_freelancer_applies_with_required_screening_answers_and_no_duplicates(): void
    {
        $f = $this->freelancer();
        $job = $this->job($this->client());
        $q = $job->questions()->create(['type' => 'yes_no', 'prompt' => 'Can you meet the deadline?', 'is_required' => true]);
        $payload = ['cover_message' => str_repeat('Thoughtful proposal. ', 3), 'proposed_amount' => 8000, 'currency' => 'ETB', 'estimated_days' => 7];

        $this->actingAs($f)->post(route('applications.store', $job), $payload)->assertSessionHasErrors("answers.{$q->id}");
        $this->actingAs($f)->post(route('applications.store', $job), $payload + ['answers' => [$q->id => 'Yes']])->assertRedirect();
        $this->assertSame(1, Application::count());
        $this->assertSame(800000, Application::first()->proposed_amount_minor);

        // Second attempt is blocked.
        $this->actingAs($f)->post(route('applications.store', $job), $payload + ['answers' => [$q->id => 'Yes']]);
        $this->assertSame(1, Application::count());
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $c = $this->client();
        $app = $this->application($this->job($c), $this->freelancer(), 'rejected');
        $this->actingAs($c)->post(route('applications.status', $app), ['status' => 'shortlisted'])->assertSessionHasErrors('status');
        $this->assertSame('rejected', $app->fresh()->status);
    }

    public function test_clients_manage_only_their_own_jobs(): void
    {
        $owner = $this->client();
        $job = $this->job($owner);
        $other = $this->client();
        $this->actingAs($other)->get(route('client.jobs.edit', $job))->assertForbidden();
        $this->actingAs($other)->get(route('client.jobs.applicants', $job))->assertForbidden();
        $this->actingAs($other)->post(route('client.jobs.status', $job), ['status' => 'closed'])->assertForbidden();
    }

    public function test_freelancers_edit_only_their_own_portfolio(): void
    {
        Storage::fake('public');
        $owner = $this->freelancer();
        $p = PortfolioProject::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'freelancer_profile_id' => $owner->freelancerProfile->id, 'title' => 'Mine', 'status' => 'draft']);
        $this->actingAs($this->freelancer())->get(route('portfolio.edit', $p))->assertForbidden();
        $this->get(route('portfolio.show', $p))->assertNotFound(); // drafts are not public
    }

    public function test_portfolio_quota_enforced_for_free_plan(): void
    {
        Storage::fake('public');
        $f = $this->freelancer();
        for ($i = 0; $i < config('marketplace.portfolio_limits.free'); $i++) {
            PortfolioProject::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'freelancer_profile_id' => $f->freelancerProfile->id, 'title' => "P$i", 'status' => 'draft']);
        }
        $this->actingAs($f)->post(route('portfolio.store'), ['title' => 'One too many', 'cover' => UploadedFile::fake()->image('c.jpg')])->assertSessionHasErrors('title');
    }

    public function test_project_files_are_participant_only_and_receipt_is_not_approval(): void
    {
        Storage::fake('local');
        $f = $this->freelancer();
        $c = $this->client();
        $app = $this->application($this->job($c), $f);
        $this->actingAs($c)->post(route('agreements.store', $app), $this->terms());
        $v = \App\Models\AgreementVersion::first();
        foreach ([$c, $f] as $u) {
            $this->actingAs($u)->post(route('agreements.confirm', $v), ['content_hash' => $v->content_hash, 'acknowledge' => 1]);
        }
        $project = \App\Models\Project::firstOrFail();

        $this->actingAs($f)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('final.pdf', 100, 'application/pdf')])->assertRedirect();
        $file = $project->files()->firstOrFail();
        $this->assertSame(64, strlen($file->sha256));

        $this->actingAs($this->client())->get(route('projects.files.download', $file))->assertForbidden();
        $this->actingAs($c)->get(route('projects.files.download', $file))->assertOk();
        $this->assertSame('available', $file->fresh()->status, 'a download must not change file status');

        $this->actingAs($c)->post(route('projects.files.receipt', $file), ['action' => 'confirm']);
        $this->assertSame('receipt_confirmed', $file->fresh()->status);
        $this->assertSame('in_progress', $project->fresh()->status, 'receipt is not acceptance of the work');
    }

    public function test_suspended_users_are_signed_out(): void
    {
        $f = $this->freelancer();
        $f->forceFill(['status' => 'suspended'])->save();
        $this->actingAs($f)->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
