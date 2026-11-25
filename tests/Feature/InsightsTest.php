<?php

namespace Tests\Feature;

use App\Models\MediaUpload;
use App\Models\Post;
use App\Models\User;
use App\Support\RichText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Insights: the admin editor, publishing rules and the public pages. */
class InsightsTest extends MarketplaceTestCase
{
    private function admin(string $role = 'super_admin'): User
    {
        $u = User::factory()->create();
        $u->forceFill(['admin_role' => $role, 'email_verified_at' => now()])->save();

        return $u;
    }

    private function asAdmin(?User $admin = null): static
    {
        return $this->actingAs($admin ?? $this->admin())->withSession(['admin_verified_at' => now()->timestamp]);
    }

    private function payload(array $over = []): array
    {
        return array_replace_recursive([
            'intent' => 'publish', 'title' => 'What creatives charge in 2026', 'excerpt' => 'Rates and turnaround.',
            'type' => 'study', 'audience' => 'everyone', 'topics' => 'Pricing, Research',
            'settings' => ['layout' => 'article', 'cover' => 'contained', 'accent' => 'green', 'toc' => '1', 'reading_time' => '1', 'author_box' => '1', 'share' => '1', 'related' => '1'],
            'blocks' => [
                'a' => ['type' => 'heading', 'body' => 'Method', 'settings' => '{"level":"h2"}'],
                'a2' => ['type' => 'heading', 'body' => 'Results', 'settings' => '{"level":"h2"}'],
                'b' => ['type' => 'text', 'body' => "Some **bold** words and a [link](https://example.com).\n\n- one\n- two", 'settings' => '{"dropcap":"1"}'],
                'c' => ['type' => 'stats', 'title' => 'At a glance', 'items' => [['a' => '68%', 'b' => 'price per project'], ['a' => '', 'b' => '']], 'settings' => '{"cols":"3"}'],
                'd' => ['type' => 'table', 'body' => "Service | Fee\nLogo | 6,000", 'settings' => '{}'],
                'e' => ['type' => 'faq', 'items' => [['a' => 'Is it real?', 'b' => 'It is a sample.']], 'settings' => '{}'],
                'f' => ['type' => 'callout', 'title' => 'Note', 'body' => 'Illustrative data.', 'settings' => '{"tone":"tip"}'],
                'g' => ['type' => 'button', 'title' => 'Hire', 'caption' => 'Browse talent', 'url' => '/freelancers', 'settings' => '{"variant":"accent"}'],
                'h' => ['type' => 'text', 'body' => '', 'settings' => '{}'], // empty → dropped
            ],
        ], $over);
    }

    public function test_an_admin_writes_and_publishes_a_post_that_the_public_can_read(): void
    {
        $this->asAdmin()->post(route('admin.posts.store'), $this->payload())->assertRedirect();
        $post = Post::firstOrFail();
        $this->assertSame('what-creatives-charge-in-2026', $post->slug);
        $this->assertTrue($post->isPublic());
        $this->assertSame(['Pricing', 'Research'], $post->topics);
        $this->assertSame(8, $post->blocks()->count(), 'the empty text block is dropped');
        $this->assertSame([['a' => '68%', 'b' => 'price per project']], $post->blocks()->where('type', 'stats')->first()->items, 'empty figures are dropped');

        auth()->logout();
        $this->get(route('insights.index'))->assertOk()->assertSee('What creatives charge in 2026');
        $this->get(route('insights.show', $post))->assertOk()
            ->assertSee('<strong>bold</strong>', false)
            ->assertSee('68%')->assertSee('Is it real?')->assertSee('Browse talent')
            ->assertSee('On this page', false)
            ->assertSee('application/ld+json', false);
        $this->assertSame(1, substr_count($this->get(route('insights.show', $post))->getContent(), 'name="description"'), 'one meta description');
        $this->assertSame(1, $post->fresh()->views);
        $this->get(route('insights.show', $post));
        $this->assertSame(1, $post->fresh()->views, 'one read per session');
        $this->get(route('insights.feed'))->assertOk()->assertSee('<title>What creatives charge in 2026</title>', false);
    }

    public function test_drafts_and_scheduled_posts_are_hidden_from_the_public_but_previewable_by_admins(): void
    {
        $admin = $this->admin();
        $this->asAdmin($admin)->post(route('admin.posts.store'), $this->payload(['intent' => 'draft', 'title' => 'Secret draft']));
        $this->asAdmin($admin)->post(route('admin.posts.store'), $this->payload(['title' => 'Future news', 'publish_at' => now()->addDays(2)->format('Y-m-d\TH:i')]));
        $draft = Post::where('title', 'Secret draft')->firstOrFail();
        $scheduled = Post::where('title', 'Future news')->firstOrFail();
        $this->assertSame('draft', $draft->state());
        $this->assertSame('scheduled', $scheduled->state());

        $this->asAdmin($admin)->get(route('insights.show', $draft))->assertOk()->assertSee('Only admins can see it');
        auth()->logout();
        $this->get(route('insights.show', $draft))->assertNotFound();
        $this->get(route('insights.show', $scheduled))->assertNotFound();
        $this->get(route('insights.index'))->assertDontSee('Secret draft')->assertDontSee('Future news');

        $this->travel(3)->days();
        $this->get(route('insights.show', $scheduled))->assertOk();
    }

    public function test_text_is_formatted_safely(): void
    {
        $html = (string) RichText::render("<script>alert(1)</script> [x](javascript:alert(1)) **ok** [site](/jobs)");
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<strong>ok</strong>', $html);
        $this->assertStringContainsString('<a href="/jobs">site</a>', $html);
    }

    public function test_unsafe_links_and_videos_are_rejected(): void
    {
        $this->asAdmin()->post(route('admin.posts.store'), $this->payload(['blocks' => ['g' => ['url' => 'javascript:alert(1)']]]))->assertSessionHasErrors('blocks');
        $this->asAdmin()->post(route('admin.posts.store'), $this->payload(['blocks' => ['x' => ['type' => 'embed', 'url' => 'https://evil.example/video', 'settings' => '{}']]]))->assertSessionHasErrors('blocks');
        $this->assertSame(0, Post::count());
    }

    public function test_only_staff_with_the_posts_permission_can_manage_posts(): void
    {
        $this->actingAs($this->client())->get(route('admin.posts'))->assertForbidden();
        $this->asAdmin($this->admin('finance'))->get(route('admin.posts'))->assertForbidden();
        $this->asAdmin($this->admin('moderator'))->get(route('admin.posts'))->assertOk();
        $this->asAdmin()->get(route('admin.posts.create'))->assertOk()->assertSee('Key figures')->assertSee('Search result preview');
    }

    public function test_status_actions_duplicate_and_delete(): void
    {
        $admin = $this->admin();
        $this->asAdmin($admin)->post(route('admin.posts.store'), $this->payload());
        $post = Post::firstOrFail();

        $this->asAdmin($admin)->post(route('admin.posts.status', $post), ['action' => 'feature'])->assertRedirect();
        $this->assertNotNull($post->fresh()->featured_at);
        $this->asAdmin($admin)->post(route('admin.posts.status', $post), ['action' => 'unpublish']);
        $this->assertFalse($post->fresh()->isPublic());
        $this->asAdmin($admin)->post(route('admin.posts.status', $post), ['action' => 'archive']);
        $this->assertSame('archived', $post->fresh()->status);

        $this->asAdmin($admin)->post(route('admin.posts.duplicate', $post))->assertRedirect();
        $copy = Post::where('id', '!=', $post->id)->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertSame($post->blocks()->count(), $copy->blocks()->count());
        $this->assertNotSame($post->slug, $copy->slug);

        $this->asAdmin($admin)->delete(route('admin.posts.destroy', $post))->assertRedirect(route('admin.posts'));
        $this->assertNull(Post::find($post->id));
        $this->assertDatabaseHas('audit_logs', ['action' => 'post.deleted', 'target_id' => $post->id]);
    }

    public function test_images_and_files_upload_and_attach(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $img = $this->asAdmin($admin)->post(route('admin.posts.upload'), ['image' => UploadedFile::fake()->image('cover.jpg', 1600, 900)], ['Accept' => 'application/json'])->assertCreated()->json();
        $file = $this->asAdmin($admin)->post(route('admin.posts.file'), ['file' => UploadedFile::fake()->create('report.pdf', 300, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated()->json();
        $this->assertStringStartsWith('posts/files/', $file['path']);
        $this->asAdmin($admin)->post(route('admin.posts.file'), ['file' => UploadedFile::fake()->create('run.exe', 10)], ['Accept' => 'application/json'])->assertUnprocessable();

        $this->asAdmin($admin)->post(route('admin.posts.store'), $this->payload([
            'cover_upload' => $img['path'],
            'blocks' => ['z' => ['type' => 'download', 'title' => 'Full report', 'existing' => $file['path'], 'caption' => 'report.pdf', 'size' => '300 KB', 'settings' => '{}']],
        ]))->assertSessionHasNoErrors();
        $post = Post::firstOrFail();
        $this->assertSame($img['path'], $post->cover_path);
        $this->assertSame(0, MediaUpload::count(), 'uploads are claimed once used');
        auth()->logout();
        $this->get(route('insights.show', $post))->assertSee('Full report')->assertSee('300 KB');

        // Someone else's pending upload can't be attached.
        $this->asAdmin()->post(route('admin.posts.store'), $this->payload(['title' => 'Other', 'cover_upload' => 'posts/not-mine.jpg']))->assertSessionHasErrors('cover_upload');
    }

    public function test_insights_index_filters_by_type_audience_and_topic(): void
    {
        $admin = $this->admin();
        $this->asAdmin($admin)->post(route('admin.posts.store'), $this->payload(['title' => 'A study for all']));
        $this->asAdmin($admin)->post(route('admin.posts.store'), $this->payload(['title' => 'A guide for clients', 'type' => 'guide', 'audience' => 'clients', 'topics' => 'Hiring']));
        auth()->logout();
        $this->get(route('insights.index', ['type' => 'guide']))->assertSee('A guide for clients')->assertDontSee('A study for all');
        $this->get(route('insights.index', ['for' => 'freelancers']))->assertSee('A study for all')->assertDontSee('A guide for clients');
        $this->get(route('insights.index', ['topic' => 'Hiring']))->assertSee('A guide for clients')->assertDontSee('A study for all');
        $this->get('/')->assertSee('Studies, guides & news', false);
    }
}
