<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DataRequest;
use App\Models\PortfolioProject;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminConsoleTest extends MarketplaceTestCase
{
    private function admin(string $role = 'super_admin'): User
    {
        $u = User::create(['name' => ucfirst($role).' Admin', 'email' => $role.'-'.uniqid().'@example.com', 'password' => 'password']);
        $u->forceFill(['admin_role' => $role, 'email_verified_at' => now()])->save();

        return $u;
    }

    private function project(User $freelancer): PortfolioProject
    {
        return PortfolioProject::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'freelancer_profile_id' => $freelancer->freelancerProfile->id, 'title' => 'Brand work', 'status' => 'published', 'published_at' => now(), 'cover_path' => 'x.jpg']);
    }

    public function test_every_console_page_renders_for_a_super_admin(): void
    {
        $this->products();
        $admin = $this->admin();
        $f = $this->freelancer();
        $c = $this->client();
        $job = $this->job($c);
        $this->application($job, $f);
        $this->project($f);
        \App\Support\DemoAnalytics::seed(7);

        $pages = ['admin.overview', 'admin.search', 'admin.users', 'admin.team', 'admin.reports', 'admin.jobs', 'admin.portfolio', 'admin.verification', 'admin.data-requests',
            'admin.applications', 'admin.agreements', 'admin.disclosures', 'admin.reviews', 'admin.orders', 'admin.orders.issues', 'admin.subscriptions', 'admin.products',
            'admin.insights.traffic', 'admin.insights.content', 'admin.insights.funnels', 'admin.insights.marketplace', 'admin.insights.export',
            'admin.categories', 'admin.featured', 'admin.announcement', 'admin.settings', 'admin.health', 'admin.audit'];
        foreach ($pages as $name) {
            $this->actingAs($admin)->get(route($name, $name === 'admin.search' ? ['q' => 'Brand'] : []))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.users.show', $f))->assertOk()->assertSee($f->email);
        $this->actingAs($admin)->get(route('admin.search.suggest', ['q' => $f->email]))->assertOk()->assertJsonFragment(['type' => 'People']);
        foreach (array_keys(\App\Http\Controllers\Admin\InsightsController::DATASETS) as $set) {
            $this->actingAs($admin)->get(route('admin.insights.download', $set))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_roles_only_reach_their_sections(): void
    {
        $finance = $this->admin('finance');
        $moderator = $this->admin('moderator');
        $member = $this->freelancer();

        $this->actingAs($finance)->get(route('admin.orders'))->assertOk();
        $this->actingAs($finance)->get(route('admin.reports'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.overview'))->assertOk()->assertDontSee(route('admin.reports'))->assertSee(route('admin.orders'));
        $this->actingAs($moderator)->get(route('admin.reports'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.orders'))->assertForbidden();
        $this->actingAs($moderator)->get(route('admin.team'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.overview'))->assertForbidden();
    }

    public function test_status_changes_need_a_reason_and_are_logged(): void
    {
        $admin = $this->admin('support');
        $f = $this->freelancer();
        $this->actingAs($admin)->post(route('admin.users.status', $f), ['status' => 'suspended'])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.users.status', $f), ['status' => 'suspended', 'reason' => 'Fake portfolio'])->assertRedirect();
        $this->assertSame('suspended', $f->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.status_changed', 'target_id' => (string) $f->id, 'reason' => 'Fake portfolio']);
        $this->actingAs($admin)->post(route('admin.users.note', $f), ['body' => 'Called them'])->assertRedirect();
        $this->assertDatabaseHas('admin_notes', ['user_id' => $f->id, 'body' => 'Called them']);
    }

    public function test_view_as_user_is_read_only_and_reversible(): void
    {
        $admin = $this->admin('support');
        $f = $this->freelancer();
        $this->actingAs($admin)->post(route('admin.users.impersonate', $f), ['reason' => 'Checking a bug report'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($f);
        $this->get(route('dashboard'))->assertOk()->assertSee('Viewing as');
        $this->put(route('settings.account'), ['name' => 'Hacked'])->assertRedirect();
        $this->assertNotSame('Hacked', $f->fresh()->name); // writes are refused
        $this->post(route('impersonation.stop'))->assertRedirect(route('admin.users.show', $f));
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.impersonation_started']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.impersonation_stopped']);
    }

    public function test_team_management_keeps_a_super_admin(): void
    {
        $super = $this->admin();
        $m = $this->freelancer();
        $this->actingAs($super)->post(route('admin.team.store'), ['email' => $m->email, 'role' => 'moderator', 'reason' => 'New hire'])->assertRedirect();
        $this->assertSame('moderator', $m->fresh()->admin_role);
        $this->actingAs($super)->post(route('admin.team.update', $m), ['role' => '', 'reason' => 'Left the team'])->assertRedirect();
        $this->assertNull($m->fresh()->admin_role);
    }

    public function test_verification_request_flows_from_settings_to_the_queue(): void
    {
        $c = $this->client();
        $this->actingAs($c)->post(route('settings.verification'), ['business_name' => 'Acme PLC', 'registration_number' => 'TIN 123'])->assertRedirect();
        $req = VerificationRequest::where('user_id', $c->id)->firstOrFail();
        $admin = $this->admin('moderator');
        $this->actingAs($admin)->get(route('admin.verification'))->assertSee('Acme PLC');
        $this->actingAs($admin)->post(route('admin.verification.decide', $req), ['decision' => 'approved', 'reason' => 'Checked TIN'])->assertRedirect();
        $this->assertTrue($c->clientProfile->fresh()->is_verified);
    }

    public function test_data_export_and_deletion(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $f = $this->freelancer();
        $admin = $this->admin('support');

        $this->actingAs($f)->post(route('settings.data-request'), ['type' => 'export'])->assertRedirect();
        $export = DataRequest::where('user_id', $f->id)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.data-requests.process', $export), ['decision' => 'complete', 'reason' => 'Identity confirmed'])->assertRedirect();
        $export->refresh();
        $this->assertTrue($export->exportAvailable());
        $this->actingAs($f)->get(route('settings.data-export', $export))->assertOk();
        $this->assertStringContainsString($f->email, Storage::disk('local')->get($export->export_path));

        $this->actingAs($f)->post(route('settings.data-request'), ['type' => 'deletion'])->assertRedirect();
        $del = DataRequest::where('type', 'deletion')->firstOrFail();
        $this->project($f);
        $this->actingAs($admin)->post(route('admin.data-requests.process', $del), ['decision' => 'complete', 'reason' => 'Confirmed by email'])->assertRedirect();
        $f->refresh();
        $this->assertSame('deleted', $f->status);
        $this->assertSame('Deleted member', $f->name);
        $this->assertSame(0, PortfolioProject::where('freelancer_profile_id', $f->freelancerProfile->id)->count());
        $this->assertNull($f->freelancerProfile->paymentDetails);
    }

    public function test_moderation_reaches_the_public_site(): void
    {
        $f = $this->freelancer();
        $p = $this->project($f);
        $admin = $this->admin('moderator');
        $this->get(route('portfolio.show', $p))->assertOk();
        $this->actingAs($admin)->post(route('admin.portfolio.moderate', $p), ['action' => 'remove', 'reason' => 'Copyright'])->assertRedirect();
        auth()->logout();
        $this->get(route('portfolio.show', $p))->assertNotFound();

        // Reports filed on the site appear in the queue and can be resolved.
        $c = $this->client();
        $this->actingAs($c)->post(route('reports.store'), ['type' => 'profile', 'id' => (string) $f->freelancerProfile->id, 'reason' => 'impersonation'])->assertRedirect();
        $r = Report::firstOrFail();
        $this->actingAs($admin)->get(route('admin.reports'))->assertSee($f->name);
        $this->actingAs($admin)->post(route('admin.reports.resolve', $r), ['status' => 'dismissed', 'reason' => 'Checked, genuine'])->assertRedirect();
        $this->assertSame('dismissed', $r->fresh()->status);
    }

    public function test_hidden_reviews_stop_counting(): void
    {
        $f = $this->freelancer();
        $c = $this->client();
        $app = $this->application($this->job($c), $f, 'accepted');
        $v = \App\Models\AgreementVersion::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'application_id' => $app->id, 'version' => 1, 'created_by' => $c->id, 'title' => 'P', 'scope' => 's', 'deliverables' => 'd',
            'amount_minor' => 100000, 'currency' => 'ETB', 'deadline' => now()->addWeek(), 'status' => 'confirmed', 'content_hash' => str_repeat('a', 64)]);
        $project = \App\Models\Project::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'application_id' => $app->id,
            'agreement_version_id' => $v->id, 'client_user_id' => $c->id, 'freelancer_user_id' => $f->id, 'title' => 'P', 'status' => 'completed']);
        $review = Review::create(['project_id' => $project->id, 'reviewer_id' => $c->id, 'reviewee_id' => $f->id, 'rating' => 1, 'body' => 'Spam']);
        $this->assertSame(1, $f->freelancerProfile->rating()['count']);
        $this->actingAs($this->admin('moderator'))->post(route('admin.reviews.toggle', $review), ['reason' => 'Fake review'])->assertRedirect();
        $this->assertSame(0, $f->freelancerProfile->fresh()->rating()['count']);
    }

    public function test_switches_and_announcement_change_the_site(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.settings.update'), ['reason' => 'Maintenance', 'flags' => ['job_posting_open' => 1, 'portfolio_publishing_open' => 1]])->assertRedirect();
        $this->assertFalse(Settings::flag('registrations_open'));
        $this->actingAs($admin)->put(route('admin.announcement.update'), ['active' => 1, 'text' => 'Back on Monday', 'tone' => 'info', 'audience' => 'all'])->assertRedirect();
        auth()->logout();
        $this->get(route('register'))->assertOk()->assertSee('Sign-ups are paused')->assertSee('Back on Monday');
        $this->post(route('register'), [])->assertForbidden();
    }

    public function test_admin_step_up_code(): void
    {
        config(['admin.step_up' => true]);
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.overview'))->assertRedirect(route('admin.verify'));
        $page = $this->actingAs($admin)->get(route('admin.verify'))->assertOk();
        $code = session('dev_code');
        $this->actingAs($admin)->post(route('admin.verify.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post(route('admin.verify.confirm'), ['code' => $code])->assertRedirect(route('admin.overview'));
        $this->actingAs($admin)->get(route('admin.overview'))->assertOk();
    }

    public function test_refund_ends_access(): void
    {
        $this->products();
        $f = $this->freelancer();
        $product = \App\Models\Product::where('code', 'freelancer_professional')->firstOrFail();
        $order = \App\Models\Order::forceCreate(['id' => (string) \Illuminate\Support\Str::ulid(), 'user_id' => $f->id, 'product_id' => $product->id, 'amount_minor' => $product->price_minor, 'currency' => 'ETB', 'status' => 'paid', 'idempotency_key' => 'k1', 'paid_at' => now()]);
        \App\Models\Entitlement::create(['user_id' => $f->id, 'product_id' => $product->id, 'order_id' => $order->id, 'starts_at' => now(), 'ends_at' => now()->addDays(30)]);
        $f->freelancerProfile->forceFill(['plan' => 'professional'])->save();
        $this->actingAs($this->admin('finance'))->post(route('admin.orders.refund', $order), ['reason' => 'Charged twice'])->assertRedirect();
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('free', $f->freelancerProfile->fresh()->plan);
    }
}
