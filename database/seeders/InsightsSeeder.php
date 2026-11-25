<?php

namespace Database\Seeders;

use App\Models\PortfolioProject;
use App\Models\Post;
use App\Models\User;
use App\Support\ArtGenerator;
use Illuminate\Database\Seeder;

/**
 * Sample Insights posts for the demo. They belong to the demo admin, so `php artisan demo:clear` removes them.
 * Figures in the sample study are illustrative and say so on the page.
 */
class InsightsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@demo.test')->first();
        if (! $admin) {
            return;
        }
        $cover = fn (string $slug, int $seed) => ArtGenerator::make("seed/posts/{$slug}.jpg", $seed, 1600, 900);
        $work = fn (string $email) => PortfolioProject::whereHas('profile.user', fn ($q) => $q->where('email', $email))
            ->where('status', 'published')->whereNotNull('cover_path')->with('blocks')->get();

        // ── 1. Study ─────────────────────────────────────────────
        $this->post($admin, [
            'title' => 'What Ethiopian creatives charge in 2026',
            'excerpt' => 'Rates, turnaround times and the services clients ask for most — from a sample survey of designers, illustrators, photographers and animators.',
            'type' => 'study', 'audience' => 'everyone', 'topics' => ['Pricing', 'Research', 'Freelancing'],
            'author_label' => config('marketplace.brand').' Research', 'featured' => true, 'days' => 4, 'views' => 1840,
            'settings' => ['layout' => 'feature', 'cover' => 'hero', 'accent' => 'blue'],
            'cover' => $cover('creative-rates-2026', 41),
        ], [
            ['callout', ['title' => 'Sample study', 'body' => 'This is demo content. The figures below are **illustrative** and show how a study looks on Insights — they are not real survey results.'], ['tone' => 'note']],
            ['text', ['body' => "Pricing creative work is hard everywhere, and harder when there's little public data to lean on. Freelancers undercharge to win their first clients; clients overpay for some services and are surprised by the cost of others.\n\nThis study brings together what creatives on the platform told us about their **rates, turnaround times and workload** — and what clients are actually hiring for."], ['dropcap' => '1', 'size' => 'lg']],
            ['stats', ['title' => 'At a glance', 'items' => [['a' => '412', 'b' => 'creatives surveyed'], ['a' => '68%', 'b' => 'set prices per project, not per hour'], ['a' => '9 days', 'b' => 'typical brand identity turnaround'], ['a' => '3.4×', 'b' => 'more briefs for packaging than a year ago']], 'caption' => 'Illustrative sample data · October 2026'], ['cols' => '4']],
            ['heading', ['body' => 'How we gathered the data']],
            ['text', ['body' => "Creatives answered a short, anonymous questionnaire about their last five paid projects: the service, the fee, the time it took and how the client found them. We removed outliers and grouped answers by service and experience level.\n\nRates are in **Ethiopian birr (ETB)** and exclude any platform fees — clients pay creatives directly on {brand}."]],
            ['heading', ['body' => 'Typical rates by service']],
            ['table', ['title' => 'Median project fee', 'body' => "Service | Entry | Intermediate | Expert\nLogo & brand mark | 6,000 | 15,000 | 40,000\nFull brand identity | 18,000 | 45,000 | 120,000\nPackaging (per SKU) | 5,000 | 12,000 | 30,000\nSocial media kit | 4,000 | 9,000 | 20,000\nProduct photography (half day) | 5,000 | 11,000 | 25,000\nExplainer animation (60s) | 15,000 | 35,000 | 90,000", 'caption' => 'ETB, median of reported projects · illustrative sample data'], ['width' => 'wide']],
            ['callout', ['title' => 'Read the ranges, not just the medians', 'body' => 'Expert fees vary by **3–4×** depending on the size of the client and how much strategy the project includes. Use the table as a starting point for a conversation, not a price list.'], ['tone' => 'tip']],
            ['heading', ['body' => "Who's hiring — and for what"]],
            ['list', ['body' => "Food & hospitality brands refreshing packaging and menus\nTech start-ups needing a brand system before launch\nNGOs commissioning illustrated reports and campaigns\nConstruction and real-estate firms ordering 3D visualisation"], ['style' => 'check']],
            ['quote', ['body' => 'Once I showed three full case studies instead of a grid of logos, clients stopped asking for discounts.', 'caption' => 'Brand designer, Addis Ababa'], ['bg' => 'accent', 'width' => 'wide', 'pad' => 'l']],
            ['heading', ['body' => 'What it means for freelancers']],
            ['list', ['body' => "Quote per project, with a clear scope and number of revision rounds\nShow your process — clients pay more when they understand the work\nAdd your typical turnaround to your profile; speed is a top hiring factor\nRaise your rates with every new case study you publish"], ['style' => 'number']],
            ['faq', ['title' => 'Questions about this study', 'items' => [['a' => 'Can I see the full data set?', 'b' => 'In a real study we would publish an anonymised data sheet as a download at the end of the post.'], ['a' => 'Why medians and not averages?', 'b' => 'A handful of very large projects would pull averages up. The median shows what a *typical* project costs.'], ['a' => 'Will you repeat the study?', 'b' => 'Yes — every year, so creatives and clients can see how rates move.']]], ['open' => '1']],
            ['button', ['title' => 'Find the right creative for your budget', 'body' => 'Browse portfolios, compare experience levels and invite creatives to your project.', 'caption' => 'Browse talent', 'url' => '/freelancers'], ['variant' => 'accent']],
        ]);

        // ── 2. Guide for freelancers ─────────────────────────────
        $tsion = $work('tsion@demo.test');
        $this->post($admin, [
            'title' => 'How to write a case study clients actually read',
            'excerpt' => 'A simple, five-part structure that turns a grid of pretty images into a story about results — and gets you hired faster.',
            'type' => 'guide', 'audience' => 'freelancers', 'topics' => ['Portfolio', 'Freelancing', 'Branding'],
            'days' => 11, 'views' => 960,
            'settings' => ['layout' => 'article', 'cover' => 'contained', 'accent' => 'violet'],
            'cover' => $cover('case-study-guide', 23),
        ], [
            ['text', ['body' => "Clients don't hire images — they hire the thinking behind them. A good case study shows **the problem, your process and the result** in a few minutes of reading.\n\nHere's the structure we see working best on {brand}."], ['size' => 'lg']],
            ['heading', ['body' => '1. Start with the brief']],
            ['text', ['body' => "One or two sentences: who the client is, what they needed and why. *\"A family-run café in Bole needed a brand that felt warm but premium enough for its new location.\"*"]],
            ['heading', ['body' => '2. Show the process, not just the result']],
            ['text', ['body' => "Sketches, mood boards and rejected directions are proof that you think. Add one or two images of the messy middle and explain what you learned from them."]],
            $tsion->first() ? ['image_text', ['title' => 'Before and after', 'body' => "Pair your final work with a sentence about the decision behind it. Why this typeface? Why these colours?\n\nThat sentence is often what convinces a client.", 'media_path' => $tsion->first()->cover_path, 'alt' => 'A finished brand identity shown on packaging'], ['side' => 'right']] : null,
            ['heading', ['body' => '3. Name the outcome']],
            ['list', ['body' => "Sales or sign-ups after launch\nWhat the client said\nWhere the work is used today"], ['style' => 'check']],
            ['callout', ['title' => 'Ask permission first', 'body' => 'Only name a client or show unreleased work when they have agreed. You can always describe the client instead — *"a regional coffee exporter"*.'], ['tone' => 'warning']],
            ['heading', ['body' => '4. Keep it scannable']],
            ['text', ['body' => "Use headings, short paragraphs and large images. Most clients skim first and read later — make the skim worth it.\n\n- One idea per paragraph\n- An image every few lines\n- A clear title for every section"]],
            ['heading', ['body' => '5. End with an invitation']],
            ['text', ['body' => 'Finish with what you can do next for a client like this one. Then share it — the [post card studio](/my/portfolio/post-card) makes a ready-to-post image of your portfolio for LinkedIn and Instagram.']],
            ['button', ['title' => 'Put it into practice', 'body' => 'Your first case studies are free.', 'caption' => 'Start your portfolio', 'url' => '/register?role=freelancer'], ['variant' => 'dark']],
        ]);

        // ── 3. Product update ────────────────────────────────────
        $this->post($admin, [
            'title' => 'New: save projects to your private Collection',
            'excerpt' => 'Bookmark work you love without liking it publicly, then find it again in one click from Explore.',
            'type' => 'update', 'audience' => 'everyone', 'topics' => ['Product'],
            'days' => 2, 'views' => 430,
            'settings' => ['layout' => 'minimal', 'cover' => 'none', 'accent' => 'green', 'toc' => '0', 'related' => '1'],
        ], [
            ['text', ['body' => "You can now **save** any project with the bookmark next to the heart. Saves are private — the creator isn't notified and nobody else can see them.\n\nLikes still work as before: they're a public thank-you that helps great work rise in *Most Loved*."], ['size' => 'lg']],
            ['list', ['title' => 'What changed', 'body' => "A bookmark button on every project card and project page\nA new Collection chip on Explore, with Saved and Liked tabs\nCategories and search work inside your Collection"], ['style' => 'check']],
            ['callout', ['title' => 'Already liked a lot of work?', 'body' => 'Everything you liked is still there — open Collection and switch to the **Liked** tab.'], ['tone' => 'success']],
            ['button', ['caption' => 'Open Explore', 'url' => '/explore'], ['variant' => 'dark', 'bg' => 'none', 'pad' => 'm']],
        ]);

        // ── 4. Guide for clients ─────────────────────────────────
        $this->post($admin, [
            'title' => 'Writing a job brief that attracts the right creative',
            'excerpt' => 'The five things every good brief includes, a template you can copy, and the questions creatives will ask you anyway.',
            'type' => 'guide', 'audience' => 'clients', 'topics' => ['Hiring', 'Briefs'],
            'days' => 19, 'views' => 712,
            'settings' => ['layout' => 'article', 'cover' => 'contained', 'accent' => 'amber'],
            'cover' => $cover('job-brief-guide', 57),
        ], [
            ['text', ['body' => "Clear briefs get better proposals — and fewer surprises later. Creatives can only price and plan what they understand.\n\nUse this checklist before you post a job."]],
            ['heading', ['body' => 'The five essentials']],
            ['list', ['body' => "Who you are and who your customers are\nWhat you need delivered, in which formats\nYour budget range — even a rough one\nWhen you need it, and any fixed launch date\nExamples of work you like (and don't)"], ['style' => 'number']],
            ['heading', ['body' => 'A template you can copy']],
            ['table', ['body' => "Section | What to write\nAbout us | One paragraph about your business and audience\nThe project | What you need and why now\nDeliverables | e.g. logo files, 3 packaging labels, social kit\nBudget | A range in ETB\nTimeline | Start date, key milestones, deadline\nReferences | 2–3 links to work you admire"], ['header' => '1']],
            ['callout', ['title' => 'Screening questions save time', 'body' => 'Add one or two questions when you post — for example *"Have you designed food packaging before?"* — and compare answers side by side.'], ['tone' => 'info']],
            ['faq', ['title' => 'Common questions', 'items' => [['a' => 'Do I pay through the platform?', 'b' => 'No. You agree the fee with the creative and **pay them directly**. We never hold or take a cut of project payments.'], ['a' => 'What if I don\'t know my budget?', 'b' => 'Check our study on [what creatives charge](/insights) and pick a range. You can adjust after the first proposals.']]]],
            ['button', ['title' => 'Ready when you are', 'caption' => 'Post a job', 'url' => '/register?role=client'], ['variant' => 'accent']],
        ]);

        // ── 5. Spotlight ─────────────────────────────────────────
        $bethlehem = $work('bethlehem@demo.test');
        if ($bethlehem->isNotEmpty()) {
            $gallery = $bethlehem->flatMap(fn ($p) => array_merge([$p->cover_path], $p->blocks->where('type', 'image')->pluck('media_path')->all()))->filter()->unique()->take(4)
                ->map(fn ($path) => ['path' => $path, 'alt' => 'Brand and graphic design work'])->values()->all();
            $this->post($admin, [
                'title' => 'Spotlight: building calm, confident brands for architects',
                'excerpt' => 'A look at the work of Bethlehem Kassa, a graphic and brand designer who turns technical firms into brands people remember.',
                'type' => 'spotlight', 'audience' => 'everyone', 'topics' => ['Spotlight', 'Branding', 'Architecture'],
                'days' => 27, 'views' => 1215,
                'settings' => ['layout' => 'feature', 'cover' => 'hero', 'accent' => 'rose', 'toc' => '0'],
                'cover_path' => $bethlehem->first()->cover_path, 'cover_alt' => 'Brand identity work by Bethlehem Kassa',
            ], [
                ['callout', ['title' => 'Demo spotlight', 'body' => 'Bethlehem is a placeholder profile used to show how creator spotlights look on Insights.'], ['tone' => 'note']],
                ['text', ['body' => "Architecture firms sell something you can't see yet. Bethlehem's job is to make them **feel** trustworthy before the first drawing.\n\nHer identities lean on restraint: generous space, one confident typeface and a colour palette borrowed from materials — concrete, timber, brass."], ['dropcap' => '1', 'size' => 'lg']],
                ['gallery', ['media' => $gallery, 'caption' => 'Selected identity work'], ['cols' => '2', 'width' => 'wide']],
                ['quote', ['body' => 'The best brief I ever got was one sentence long and completely honest about the problem.', 'caption' => 'Bethlehem Kassa'], ['align' => 'center', 'width' => 'wide', 'pad' => 'l']],
                ['text', ['body' => 'See more of her case studies — and invite her to your next project — on [her profile](/freelancers).']],
            ]);
        }

        // ── 6. Draft + scheduled (to show admin states) ──────────
        $this->post($admin, [
            'title' => 'Pricing guide for motion designers', 'excerpt' => 'How to quote animation work by length, style and revision rounds.',
            'type' => 'guide', 'audience' => 'freelancers', 'topics' => ['Pricing', 'Motion'], 'status' => 'draft',
            'settings' => ['accent' => 'violet'],
        ], [
            ['text', ['body' => 'Draft in progress — outline: per-second pricing, style multipliers, revision rounds, usage rights.']],
        ]);
        $this->post($admin, [
            'title' => 'Coming soon: verified client badges', 'excerpt' => 'A clearer signal of which clients have confirmed their business details.',
            'type' => 'update', 'audience' => 'freelancers', 'topics' => ['Product', 'Trust'], 'days' => -3,
            'settings' => ['layout' => 'minimal', 'cover' => 'none', 'accent' => 'green'],
        ], [
            ['text', ['body' => 'We are rolling out verified badges for clients who confirm their business details.']],
        ]);
    }

    /** Creates one post with its blocks. Block spec: [type, fields, settings]. */
    private function post(User $author, array $p, array $blocks): Post
    {
        $brand = config('marketplace.brand');
        $post = Post::create([
            'title' => $p['title'], 'slug' => Post::uniqueSlug($p['title']), 'excerpt' => $p['excerpt'] ?? null,
            'type' => $p['type'], 'audience' => $p['audience'] ?? 'everyone', 'topics' => $p['topics'] ?? [],
            'author_id' => $author->id, 'author_label' => $p['author_label'] ?? null,
            'status' => $p['status'] ?? 'published',
            'published_at' => ($p['status'] ?? 'published') === 'published' ? now()->subDays($p['days'] ?? 1)->setTime(9, 30) : null,
            'featured_at' => ! empty($p['featured']) ? now() : null,
            'settings' => Post::cleanSettings($p['settings'] ?? []),
            'cover_path' => $p['cover_path'] ?? $p['cover'] ?? null, 'cover_alt' => $p['cover_alt'] ?? null,
            'views' => $p['views'] ?? 0,
        ]);
        $i = 0;
        foreach (array_filter($blocks) as $spec) {
            [$type, $f] = $spec;
            $settings = $spec[2] ?? [];
            $post->blocks()->create([
                'type' => $type,
                'title' => $f['title'] ?? null,
                'body' => isset($f['body']) ? str_replace('{brand}', $brand, $f['body']) : null,
                'caption' => $f['caption'] ?? null,
                'url' => $f['url'] ?? null,
                'alt' => $f['alt'] ?? null,
                'media_path' => $f['media_path'] ?? null,
                'media' => $f['media'] ?? null,
                'items' => $f['items'] ?? null,
                'settings' => \App\Models\PostBlock::cleanSettings($type, $settings),
                'sort_order' => $i++,
            ]);
        }
        $post->load('blocks');
        $post->forceFill(['reading_minutes' => $post->computeReadingMinutes()])->saveQuietly();

        return $post;
    }
}
