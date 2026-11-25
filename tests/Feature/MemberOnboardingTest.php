<?php

namespace Tests\Feature;

use App\Models\User;

/** New accounts start as plain members unless they came in through a freelancer or client link. */
class MemberOnboardingTest extends MarketplaceTestCase
{
    private function signUp(array $extra = []): User
    {
        $this->post('/register', array_merge([
            'name' => 'Selam Bekele', 'email' => 'selam@example.com', 'password' => 'secret123',
            'password_confirmation' => 'secret123', 'terms' => 1,
        ], $extra))->assertRedirect(route('verification.notice'));

        return User::where('email', 'selam@example.com')->firstOrFail();
    }

    private function member(): User
    {
        return User::factory()->create();
    }

    public function test_a_general_sign_up_creates_a_member_without_a_role(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Just exploring');
        $u = $this->signUp();
        $this->assertFalse($u->is_freelancer);
        $this->assertFalse($u->is_client);
        $this->assertNull($u->freelancerProfile);
        $this->assertSame('none', $u->workspace());

        auth()->logout();
        $this->signUpAs('member', 'member@example.com')->each(fn ($u) => $this->assertSame('none', $u->workspace()));
    }

    private function signUpAs(string $role, string $email)
    {
        $this->post('/register', ['name' => 'Another Person', 'email' => $email, 'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => 1, 'role' => $role]);

        return collect([User::where('email', $email)->firstOrFail()]);
    }

    public function test_freelancer_and_client_links_start_people_in_that_role(): void
    {
        $this->get(route('register', ['role' => 'client']))->assertOk();
        $c = $this->signUp(['role' => 'client']);
        $this->assertTrue($c->is_client);
        $this->assertSame('client', $c->workspace());

        auth()->logout();
        $f = $this->signUpAs('freelancer', 'fl@example.com')->first();
        $this->assertTrue($f->is_freelancer);
        $this->assertNotNull($f->freelancerProfile);
    }

    public function test_a_member_sees_the_welcome_page_and_browsing_links(): void
    {
        $m = $this->member();
        $this->actingAs($m)->get(route('dashboard'))->assertOk()
            ->assertSee('How would you like to use')->assertSee('Become a freelancer')->assertSee('Hire creative talent');
        $this->actingAs($m)->get(route('home'))->assertOk()
            ->assertSee("You're browsing as a member", false)
            ->assertDontSee('Find Work')->assertDontSee('My Jobs');
    }

    public function test_a_member_is_offered_the_role_an_action_needs_then_sent_back(): void
    {
        $m = $this->member();
        $job = $this->job($this->client());

        $this->actingAs($m)->get(route('applications.create', $job))->assertRedirect(route('dashboard', ['need' => 'freelancer']));
        $this->actingAs($m)->get(route('dashboard', ['need' => 'freelancer']))->assertSee('you need a freelancer profile');

        $this->actingAs($m)->post(route('workspace.enable'), ['role' => 'freelancer'])
            ->assertRedirect(route('applications.create', $job));
        $this->assertTrue($m->fresh()->is_freelancer);
    }

    public function test_admins_can_filter_undecided_members(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['admin_role' => 'super_admin', 'email_verified_at' => now()])->save();
        $undecided = User::factory()->create(['name' => 'Undecided Person']);
        $freelancer = $this->freelancer();

        $this->actingAs($admin)->withSession(['admin_verified_at' => now()->timestamp]);
        $this->get(route('admin.users', ['role' => 'member']))->assertOk()->assertSee('Undecided Person')->assertDontSee($freelancer->email)->assertDontSee($admin->email);
        $this->get(route('admin.overview'))->assertOk()->assertSee('Undecided');
    }
}
