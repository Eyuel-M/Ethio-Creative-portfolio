<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileMediaEditorTest extends MarketplaceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_editors_appear_only_on_your_own_profile(): void
    {
        $f = $this->freelancer();
        $url = route('freelancers.show', $f->freelancerProfile);

        $this->actingAs($f)->get($url)->assertOk()->assertSee('Change cover')->assertSee('Change photo')->assertSee('open-cover-editor');
        $this->actingAs($this->client())->get($url)->assertOk()->assertDontSee('Change cover')->assertDontSee('open-avatar-editor');
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk()->assertDontSee('Change cover');
    }

    public function test_photo_upload_replace_and_remove(): void
    {
        $f = $this->freelancer();
        $this->actingAs($f)->post(route('settings.avatar'), ['avatar' => UploadedFile::fake()->image('a.jpg', 400, 400)])->assertRedirect();
        $first = $f->fresh()->avatar_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($f)->post(route('settings.avatar'), ['avatar' => UploadedFile::fake()->image('b.png', 400, 400)]);
        $second = $f->fresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first); // old file cleaned up

        $this->actingAs($f)->post(route('settings.avatar'), ['remove' => 1]);
        $this->assertNull($f->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($second);

        $this->actingAs($f)->post(route('settings.avatar'), ['avatar' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('avatar');
    }

    public function test_cover_upload_reposition_and_remove(): void
    {
        $f = $this->freelancer();
        $p = $f->freelancerProfile;

        $this->actingAs($f)->post(route('settings.cover'), ['cover_position' => 30])->assertSessionHasErrors('cover'); // nothing to position yet

        $this->actingAs($f)->post(route('settings.cover'), ['cover' => UploadedFile::fake()->image('c.jpg', 1600, 900), 'cover_position' => 20])->assertRedirect();
        $p->refresh();
        Storage::disk('public')->assertExists($p->cover_path);
        $this->assertSame(20, (int) $p->cover_position);
        $this->actingAs($f)->get(route('freelancers.show', $p))->assertSee('object-position: 50% 20%', false);

        $path = $p->cover_path;
        $this->actingAs($f)->post(route('settings.cover'), ['cover_position' => 75]); // drag only, same image
        $p->refresh();
        $this->assertSame($path, $p->cover_path);
        $this->assertSame(75, (int) $p->cover_position);

        $this->actingAs($f)->post(route('settings.cover'), ['cover_position' => 140])->assertSessionHasErrors('cover_position');

        $this->actingAs($f)->post(route('settings.cover'), ['remove' => 1]);
        $p->refresh();
        $this->assertNull($p->cover_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_clients_cannot_set_a_freelancer_cover(): void
    {
        $this->actingAs($this->client())->post(route('settings.cover'), ['cover' => UploadedFile::fake()->image('c.jpg'), 'cover_position' => 50])->assertRedirect(route('settings'));
    }

    public function test_settings_account_has_no_photo_controls(): void
    {
        foreach ([$this->freelancer(), $this->client()] as $u) {
            $this->actingAs($u)->get(route('settings', ['tab' => 'account']))->assertOk()
                ->assertDontSee('name="avatar"', false)->assertDontSee('open-avatar-editor')->assertDontSee('Change photo');
        }
        $this->actingAs($this->freelancer())->get(route('settings', ['tab' => 'profile']))->assertOk()->assertDontSee('name="cover"', false);
    }

    public function test_profile_menu_goes_to_your_profile(): void
    {
        $f = $this->freelancer();
        $this->actingAs($f)->get(route('dashboard'))->assertSee(route('profile'));
        $this->actingAs($f)->get(route('profile'))->assertRedirect(route('freelancers.show', $f->freelancerProfile));

        $c = $this->client();
        $this->actingAs($c)->get(route('dashboard'))->assertSee(route('profile'));
        $this->actingAs($c)->get(route('profile'))->assertOk()->assertSee($c->name)->assertSee('open-avatar-editor')->assertSee(route('settings.avatar'))->assertSee('Edit details');
    }

}
