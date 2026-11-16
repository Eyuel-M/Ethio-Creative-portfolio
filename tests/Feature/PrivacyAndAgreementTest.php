<?php

namespace Tests\Feature;

use App\Models\AgreementVersion;
use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class PrivacyAndAgreementTest extends MarketplaceTestCase
{
    public function test_payment_details_are_encrypted_at_rest(): void
    {
        $f = $this->freelancer();
        $raw = DB::table('freelancer_payment_details')->first();
        $this->assertNotSame('1000123456789', $raw->encrypted_cbe_account_number);
        $this->assertStringNotContainsString('1000123456789', $raw->encrypted_cbe_account_number);
        $this->assertSame('1000123456789', $f->freelancerProfile->paymentDetails->encrypted_cbe_account_number);
    }

    public function test_private_fields_absent_from_public_profile_and_directory(): void
    {
        $f = $this->freelancer();
        foreach ([route('freelancers.show', $f->freelancerProfile), route('freelancers.index')] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('1000123456789')->assertDontSee('0911223344')->assertDontSee('+251900111222');
        }
        $this->assertArrayNotHasKey('contact_phone', $f->freelancerProfile->toArray());
    }

    public function test_client_cannot_see_payment_details_on_application_before_confirmation(): void
    {
        $f = $this->freelancer();
        $c = $this->client();
        $app = $this->application($this->job($c), $f);

        $this->actingAs($c)->get(route('applications.show', $app))->assertOk()
            ->assertDontSee('1000123456789')->assertDontSee('0911223344');
    }

    public function test_details_revealed_only_after_both_confirm_same_version(): void
    {
        $f = $this->freelancer();
        $c = $this->client();
        $app = $this->application($this->job($c), $f);

        $this->actingAs($c)->post(route('agreements.store', $app), $this->terms())->assertRedirect();
        $v = AgreementVersion::firstOrFail();

        // Only the client confirms → no project, nothing revealed.
        $this->actingAs($c)->post(route('agreements.confirm', $v), ['content_hash' => $v->content_hash, 'acknowledge' => 1]);
        $this->assertSame(0, Project::count());

        // Freelancer confirms the same version → project created.
        $this->actingAs($f)->post(route('agreements.confirm', $v), ['content_hash' => $v->content_hash, 'acknowledge' => 1])->assertRedirect();
        $project = Project::firstOrFail();
        $this->assertSame('accepted', $app->fresh()->status);

        $this->actingAs($c)->get(route('projects.show', $project))->assertOk()->assertSee('1000123456789')->assertSee('0911223344');
        $this->assertTrue(AuditLog::where('action', 'payment_details.revealed')->exists());

        // A stranger cannot open the workspace.
        $this->actingAs($this->client())->get(route('projects.show', $project))->assertForbidden();
    }

    public function test_changed_terms_require_fresh_confirmation_from_both(): void
    {
        $f = $this->freelancer();
        $c = $this->client();
        $app = $this->application($this->job($c), $f);

        $this->actingAs($c)->post(route('agreements.store', $app), $this->terms());
        $v1 = AgreementVersion::where('version', 1)->firstOrFail();
        $this->actingAs($c)->post(route('agreements.confirm', $v1), ['content_hash' => $v1->content_hash, 'acknowledge' => 1]);

        // Freelancer proposes changed price → v1 superseded.
        $this->actingAs($f)->post(route('agreements.store', $app), $this->terms(['amount' => 12000]));
        $this->assertSame('superseded', $v1->fresh()->status);

        // Confirming the stale version fails and creates no project.
        $this->actingAs($f)->post(route('agreements.confirm', $v1), ['content_hash' => $v1->content_hash, 'acknowledge' => 1])->assertSessionHasErrors('agreement');
        $this->assertSame(0, Project::count());

        $v2 = AgreementVersion::where('version', 2)->firstOrFail();
        $this->actingAs($f)->post(route('agreements.confirm', $v2), ['content_hash' => $v2->content_hash, 'acknowledge' => 1]);
        $this->assertSame(0, Project::count(), 'client confirmation of v1 must not carry over to v2');
        $this->actingAs($c)->post(route('agreements.confirm', $v2), ['content_hash' => $v2->content_hash, 'acknowledge' => 1]);
        $this->assertSame(1, Project::count());
    }

    public function test_non_participant_cannot_propose_or_confirm(): void
    {
        $f = $this->freelancer();
        $c = $this->client();
        $app = $this->application($this->job($c), $f);
        $stranger = $this->freelancer();

        $this->actingAs($stranger)->post(route('agreements.store', $app), $this->terms())->assertForbidden();
        $this->actingAs($c)->post(route('agreements.store', $app), $this->terms());
        $v = AgreementVersion::firstOrFail();
        $this->actingAs($stranger)->post(route('agreements.confirm', $v), ['content_hash' => $v->content_hash, 'acknowledge' => 1])->assertForbidden();
    }
}
