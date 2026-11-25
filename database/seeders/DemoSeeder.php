<?php

namespace Database\Seeders;

use App\Actions\AgreementService;
use App\Models\Application;
use App\Models\Category;
use App\Models\ClientProfile;
use App\Models\Conversation;
use App\Models\FreelancerPaymentDetails;
use App\Models\FreelancerProfile;
use App\Models\Invitation;
use App\Models\JobPost;
use App\Models\PortfolioProject;
use App\Models\Product;
use App\Models\Review;
use App\Models\Skill;
use App\Models\User;
use App\Support\ArtGenerator;
use App\Support\QuestionTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Placeholder (demo) content: creatives, portfolios, jobs, applications, projects, reviews, messages.
 * Every placeholder account uses an @demo.test email so `php artisan demo:clear` can remove it all
 * without touching real accounts. Synthetic development data only — never run against production.
 * Demo logins (password: "password"): freelancer@demo.test, client@demo.test, admin@demo.test,
 * member@demo.test (signed up, hasn't chosen freelancer or client yet)
 */
class DemoSeeder extends Seeder
{
    private int $art = 1;

    public function run(): void
    {
        if (\App\Support\DemoData::present()) {
            if (\App\Support\DemoData::complete()) {
                $this->command?->warn('Demo data is already loaded. Run `php artisan demo:seed --fresh` to reload it.');

                return;
            }
            // Half-loaded (e.g. an earlier run crashed): clean it up and load everything again.
            $this->command?->warn('Found incomplete placeholder data — removing it and loading a fresh set…');
            \App\Support\DemoData::clear();
        }
        Storage::disk('public')->deleteDirectory('seed');
        \Illuminate\Support\Facades\DB::transaction(fn () => $this->load());
        $this->adminQueues();
        \App\Support\DemoAnalytics::seed();
    }

    /** A few items in the admin work queues, plus realistic sign-in times. */
    private function adminQueues(): void
    {
        $users = User::where('email', 'like', '%'.\App\Support\DemoData::DOMAIN)->get();
        foreach ($users as $u) {
            $seen = now()->subHours(mt_rand(1, 24 * 20));
            $u->forceFill(['last_seen_at' => $seen, 'last_login_at' => $seen->copy()->subMinutes(mt_rand(5, 300))])->saveQuietly();
        }
        $by = fn ($email) => $users->firstWhere('email', $email);
        $project = PortfolioProject::where('status', 'published')->whereHas('profile.user', fn ($q) => $q->where('email', 'meron@demo.test'))->first();
        $job = JobPost::live()->latest()->first();
        foreach ([
            [$by('dawit@demo.test'), 'portfolio', $project?->id, 'copyright', 'I think the second image is from another designer\'s Behance.'],
            [$by('freelancer@demo.test'), 'job', $job?->id, 'scam', 'The client asked me to pay a "registration fee" in chat before starting.'],
            [$by('client@demo.test'), 'profile', $by('abel@demo.test')?->freelancerProfile?->id, 'impersonation', 'This profile uses photos from a wedding studio in Bole.'],
        ] as [$reporter, $type, $id, $reason, $details]) {
            if ($reporter && $id) {
                \App\Models\Report::create(['reporter_id' => $reporter->id, 'reportable_type' => $type, 'reportable_id' => (string) $id, 'reason' => $reason, 'details' => $details, 'created_at' => now()->subHours(mt_rand(3, 60))]);
            }
        }
        foreach (['saron@demo.test' => ['Gojo Tech PLC', 'TIN 0045821193', 'https://gojo.example.com'], 'elias@demo.test' => ['Lalibela Tours & Travel', 'Licence AA/14/7731', null]] as $email => [$name, $reg, $site]) {
            if ($u = $by($email)) {
                if (! $u->clientProfile?->is_verified) {
                    \App\Models\VerificationRequest::create(['user_id' => $u->id, 'business_name' => $name, 'registration_number' => $reg, 'website' => $site, 'message' => 'We post 2–3 design jobs a month.']);
                }
            }
        }
        if ($u = $by('natnael@demo.test')) {
            \App\Models\DataRequest::create(['user_id' => $u->id, 'requester_email' => $u->email, 'type' => 'export', 'note' => 'I want a copy of my portfolio texts.']);
        }
    }

    private function load(): void
    {
        $realNotifications = Notification::getFacadeRoot();
        Notification::fake(); // seeded history shouldn't spam the mail log; we add a few notifications explicitly below.

        $this->products();
        $cats = $this->taxonomy();

        // --- Admin ---
        $admin = $this->user('Selam Admin', 'admin@demo.test');
        $admin->forceFill(['admin_role' => 'super_admin'])->save();

        // --- Members who signed up but haven't picked freelancer or client yet ---
        foreach ([['Dawit Alemayehu', 'member@demo.test', 1], ['Mahlet Girma', 'mahlet@demo.test', 3], ['Yared Tesfaye', 'yared@demo.test', 20]] as [$n, $e, $seen]) {
            $this->user($n, $e)->forceFill(['created_at' => now()->subDays($seen + 2), 'last_seen_at' => now()->subDays($seen), 'last_login_at' => now()->subDays($seen)])->save();
        }

        // --- Freelancers ---
        $people = [
            ['Hana Tesfaye', 'freelancer@demo.test', 'brand-identity', 'Brand Identity Designer', 'Addis Ababa', 'expert', 8, 1200, ['Branding', 'Logo Design', 'Typography', 'Packaging'], ['Illustrator', 'Figma', 'Photoshop']],
            ['Dawit Bekele', 'dawit@demo.test', 'ui-ux-design', 'Product & UI/UX Designer', 'Addis Ababa', 'expert', 6, 1500, ['UI Design', 'UX Research', 'Prototyping', 'Design Systems'], ['Figma', 'Framer']],
            ['Meron Alemu', 'meron@demo.test', 'illustration', 'Illustrator & Muralist', 'Bahir Dar', 'intermediate', 4, 800, ['Illustration', 'Character Design', 'Editorial'], ['Procreate', 'Photoshop']],
            ['Yonas Girma', 'yonas@demo.test', 'video-animation', 'Music Branding & Motion Designer', 'Addis Ababa', 'expert', 7, 1400, ['Music Branding', 'Album Artwork', 'Motion Graphics'], ['After Effects', 'Illustrator', 'Photoshop']],
            ['Liya Haile', 'liya@demo.test', 'photography', 'Product & Food Photographer', 'Hawassa', 'intermediate', 5, 900, ['Product Photography', 'Food Photography', 'Retouching'], ['Lightroom', 'Capture One']],
            ['Samuel Tadesse', 'samuel@demo.test', 'web-design', 'Brand Identity & Web Designer', 'Adama', 'expert', 6, 1300, ['Brand Identity', 'Web Design', 'Logo Design', 'Corporate Identity'], ['Illustrator', 'Figma', 'WordPress']],
            ['Bethlehem Kassa', 'bethlehem@demo.test', 'graphic-design', 'Graphic & Brand Designer', 'Addis Ababa', 'intermediate', 5, 950, ['Brand Identity', 'Social Media Design', 'Signage', 'Graphic Design'], ['Illustrator', 'Photoshop', 'InDesign']],
            ['Natnael Worku', 'natnael@demo.test', 'copywriting', 'Bilingual Copywriter (Amharic / English)', 'Mekelle', 'intermediate', 4, 700, ['Copywriting', 'Brand Voice', 'Translation'], ['Notion', 'Google Docs']],
            ['Ruth Mengistu', 'ruth@demo.test', 'ui-ux-design', 'Mobile App Designer', 'Addis Ababa', 'intermediate', 3, 1100, ['Mobile Design', 'UI Design', 'Prototyping'], ['Figma']],
            ['Abel Getachew', 'abel@demo.test', 'video-animation', 'Videographer & Editor', 'Dire Dawa', 'entry', 2, 600, ['Video Editing', 'Color Grading', 'Videography'], ['Premiere Pro', 'DaVinci Resolve']],
            ['Tsion Abebe', 'tsion@demo.test', 'brand-identity', 'Packaging & Label Designer', 'Gondar', 'expert', 9, 1300, ['Packaging', 'Label Design', 'Branding'], ['Illustrator', 'Dimension']],
            ['Kaleb Mulugeta', 'kaleb@demo.test', 'illustration', '3D Artist & Illustrator', 'Addis Ababa', 'intermediate', 3, 950, ['3D Illustration', 'Blender', 'Character Design'], ['Blender', 'Cinema 4D']],
        ];
        $projectTitles = [
            'brand-identity' => ['Buna House — coffee roastery identity', 'Selam Spa brand system', 'Teff & Co. bakery rebrand', 'Habesha Weavers visual identity'],
            'ui-ux-design' => ['Telehealth booking app', 'Birr budget tracker UI', 'Ride-share driver onboarding', 'Merkato B2B ordering dashboard'],
            'illustration' => ['Children\'s book: The Clever Hyena', 'Addis street life mural', 'Editorial series for Addis Fortune', 'Folk-tale character set'],
            'video-animation' => ['Fintech explainer animation', 'Ethiopian Airlines-style safety short (concept)', 'Music video edit — Tizita', 'Logo motion pack'],
            'photography' => ['Injera & stew menu shoot', 'Leather goods catalogue', 'Coffee ceremony editorial', 'Skincare product line'],
            'web-design' => ['NGO donation landing page', 'Boutique hotel website', 'Online course landing page', 'Real-estate listing site'],
            'graphic-design' => ['Timket festival poster series', 'Gym social media kit', 'Concert flyer collection'],
            'copywriting' => ['Bank app microcopy (Amharic)', 'Tourism campaign taglines', 'Product launch emails'],
        ];

        // Real case studies by Eyuel Mulat, Luva Haregot and Mika Fekadu, shown on these demo
        // profiles with permission — see database/seeders/demo-images/CREDITS.md.
        $realProjects = [
            'freelancer@demo.test' => ['eyuel-mulat/sone-academy', 'luva-haregot/vektra', 'mika-fekadu/haben-fabrics', 'mika-fekadu/arts-by-aklil-logo-design'],
            'tsion@demo.test' => ['eyuel-mulat/malden-cafe-and-restaurant', 'luva-haregot/ivylounge', 'mika-fekadu/plantable-bags'],
            'bethlehem@demo.test' => ['eyuel-mulat/adadi-studio', 'luva-haregot/zias', 'luva-haregot/cordon'],
            'kaleb@demo.test' => ['luva-haregot/vivbeverages', 'mika-fekadu/roa-trading'],
            'samuel@demo.test' => ['mika-fekadu/cosmos-mto', 'mika-fekadu/yaconbuilding-contractor', 'mika-fekadu/ashara-architecture'],
            'yonas@demo.test' => ['mika-fekadu/dess-music', 'mika-fekadu/mastewal-eyayu'],
            'natnael@demo.test' => ['mika-fekadu/kalandras-podcast', 'mika-fekadu/gouder-wines'],
        ];

        $freelancers = collect();
        foreach ($people as $i => [$name, $email, $catSlug, $title, $loc, $level, $yrs, $rate, $skills, $tools]) {
            $u = $this->user($name, $email, $i % 3 === 0 ? 'freelancer' : 'freelancer');
            $cat = $cats[$catSlug];
            $profile = FreelancerProfile::forceCreate([
                'user_id' => $u->id, 'slug' => Str::slug($name), 'category_id' => $cat->id, 'title' => $title,
                'bio' => "I'm {$name}, a {$title} based in {$loc} with {$yrs} years of experience. I help Ethiopian brands and international teams turn ideas into clear, memorable work — with honest timelines and lots of communication along the way.",
                'location' => $loc.', Ethiopia', 'availability' => $i % 5 === 3 ? 'busy' : 'available', 'experience_level' => $level,
                'years_experience' => $yrs, 'hourly_rate_minor' => $rate * 100, 'currency' => 'ETB', 'languages' => ['Amharic', 'English'], 'tools' => $tools,
                'website' => 'https://'.Str::slug($name).'.example.com', 'contact_phone' => '+2519'.str_pad((string) (11000000 + $i * 7311), 8, '0', STR_PAD_LEFT),
                'contact_email' => $email, 'is_public' => true, 'plan' => $i < 2 ? 'professional' : 'free',
                'featured_until' => in_array($i, [1, 10]) ? now()->addDays(10) : null, 'profile_views' => mt_rand(40, 900),
            ]);
            $profile->skills()->sync(collect($skills)->map(fn ($s) => Skill::firstOrCreate(['slug' => Str::slug($s)], ['name' => $s, 'category_id' => $cat->id])->id));

            $pd = new FreelancerPaymentDetails(['freelancer_profile_id' => $profile->id]);
            if ($i % 4 !== 3) {
                $pd->encrypted_cbe_account_number = '1000'.str_pad((string) (123456789 + $i * 1111), 9, '0', STR_PAD_LEFT);
            }
            if ($i % 2 === 0) {
                $pd->encrypted_telebirr_number = '09'.str_pad((string) (11223344 + $i * 1357), 8, '0', STR_PAD_LEFT);
            }
            $pd->preferred_method = $pd->encrypted_cbe_account_number ? 'cbe' : ($pd->encrypted_telebirr_number ? 'telebirr' : null);
            $pd->refreshStatus();
            $pd->save();

            $titles = $projectTitles[$catSlug] ?? ['Selected work'];
            $limit = min(count($titles), $profile->portfolioLimit());
            // Real case studies (used with permission) lead some portfolios; the rest use generated placeholder art.
            $real = $realProjects[$email] ?? [];
            foreach ($real as $k => $slug) {
                $this->realPortfolio($profile, $cat, $slug, $k);
            }
            foreach (array_slice($titles, 0, max(0, $limit - count($real))) as $k => $pt) {
                $this->portfolio($profile, $cat, $pt, $k + count($real));
            }
            $freelancers->push($profile);
        }

        // --- Clients ---
        $clients = collect([
            ['Mikias Fikru', 'client@demo.test', 'company', 'Buna House Coffee', 'Food & Beverage', 'Addis Ababa', true],
            ['Saron Yilma', 'saron@demo.test', 'company', 'Gojo Tech', 'Technology', 'Addis Ababa', true],
            ['Elias Mamo', 'elias@demo.test', 'company', 'Lalibela Tours', 'Travel', 'Lalibela', false],
            ['Hiwot Desta', 'hiwot@demo.test', 'individual', null, 'Education', 'Hawassa', false],
            ['Kidus Fasil', 'kidus@demo.test', 'company', 'Abay Bank (demo)', 'Finance', 'Bahir Dar', true],
        ])->map(function ($c) {
            [$name, $email, $type, $company, $industry, $loc, $verified] = $c;
            $u = $this->user($name, $email, 'client');

            return ClientProfile::forceCreate(['user_id' => $u->id, 'type' => $type, 'company_name' => $company, 'industry' => $industry, 'location' => $loc, 'website' => $company ? 'https://'.Str::slug($company).'.example.com' : null, 'contact_phone' => '+251911'.mt_rand(100000, 999999), 'is_verified' => $verified]);
        });

        // --- Jobs ---
        $jobs = [
            [0, 'brand-identity', 'Let\'s Collaborate on Buna House\'s Packaging Redesign', 'We are looking for a creative person who can redesign our coffee bag packaging and build a consistent label system across 6 roasts.', 'fixed', 25000, 40000, 'expert', ['Packaging', 'Branding', 'Label Design', 'Illustrator'], 1],
            [1, 'web-design', 'Build/Edit Landing Page for Online Course', 'Have a current landing page for our coding bootcamp already. Can either edit that one for the new cohort or make a new one — must be fast and mobile-first.', 'hourly', 600, 900, 'entry', ['Web Design', 'Landing Page', 'Webflow', 'Responsive Design'], 2],
            [1, 'ui-ux-design', 'UI Designer for Mobile Banking App Refresh', 'Redesign 25 core screens of our wallet app, with an Amharic-first type system and accessible color contrast.', 'fixed', 80000, 120000, 'expert', ['UI Design', 'Mobile Design', 'Design Systems', 'Figma'], 4],
            [2, 'photography', 'Product Photographer for Tour Brochure', 'Shoot lodge interiors and local crafts for our 2027 brochure. Two-day shoot in Lalibela; travel covered.', 'fixed', 30000, 45000, 'intermediate', ['Product Photography', 'Retouching'], 3],
            [2, 'video-animation', 'Short Promo Video Editor (60s + 15s cut)', 'Edit existing drone and handheld footage into a 60s promo and a 15s vertical cut. Music licensing support appreciated.', 'fixed', 15000, 22000, 'intermediate', ['Video Editing', 'Color Grading', 'Premiere Pro'], 5],
            [3, 'illustration', 'Illustrations for Amharic Children\'s Reader', 'Twelve full-page illustrations and a cover for a grade-2 reader. Warm, inclusive and culturally grounded style.', 'fixed', 20000, 35000, 'intermediate', ['Illustration', 'Character Design', 'Editorial'], 6],
            [4, 'graphic-design', 'Social Media Designer — Monthly Content Kit', 'Design 20 posts/month in Amharic and English for our branch campaigns. Templates in Canva or Figma.', 'hourly', 400, 600, 'entry', ['Social Media Design', 'Graphic Design', 'Canva'], 7],
            [4, 'copywriting', 'Bilingual Copywriter for App Microcopy', 'Write and review error messages, onboarding and notifications in Amharic and English for our mobile app.', 'hourly', 500, 800, 'intermediate', ['Copywriting', 'Translation', 'Brand Voice'], 8],
            [1, 'video-animation', 'Explainer Animation for Payments Feature', '90-second 2D explainer showing how merchants accept QR payments. Script is ready; need storyboard, animation and VO sync.', 'fixed', 45000, 70000, 'expert', ['Motion Graphics', '2D Animation', 'After Effects'], 9],
            [0, 'web-design', 'Website Designer for Café Chain', 'We\'re looking for a website designer who can redesign our company website with menu, locations and online ordering mockups — Figma-based.', 'hourly', 800, 1200, 'expert', ['Web Design', 'Figma', 'Prototyping'], 10],
            [3, 'brand-identity', 'Logo & Brand Kit for Tutoring Startup', 'Simple, friendly identity: logo, colors, type and a one-page brand guide. Budget-conscious but serious about quality.', 'fixed', 8000, 14000, 'entry', ['Logo Design', 'Branding'], 11],
            [2, 'ui-ux-design', 'UX Audit of Tour Booking Flow', 'Review our booking flow, run 5 user interviews and deliver prioritized recommendations with annotated wireframes.', 'fixed', 25000, 35000, 'intermediate', ['UX Research', 'Prototyping'], 13],
        ];
        $jobModels = collect();
        foreach ($jobs as $n => [$ci, $cat, $title, $desc, $btype, $min, $max, $level, $skills, $daysAgo]) {
            $job = JobPost::forceCreate([
                'id' => (string) Str::ulid(), 'client_profile_id' => $clients[$ci]->id, 'category_id' => $cats[$cat]->id, 'title' => $title,
                'description' => $desc."\n\nPlease share relevant portfolio work and tell us how you'd approach this. We value clear communication and realistic timelines.",
                'deliverables' => "Source files\nExported assets for print and web\nA short handover walkthrough",
                'budget_type' => $btype, 'budget_min_minor' => $min * 100, 'budget_max_minor' => $max * 100, 'currency' => 'ETB',
                'duration' => $btype === 'hourly' ? 'Ongoing, ~10 hrs/week' : (mt_rand(2, 6).' weeks'), 'deadline' => now()->addWeeks(mt_rand(4, 10)),
                'experience_level' => $level, 'location' => $clients[$ci]->location, 'is_remote' => $n % 3 !== 1, 'hires_needed' => 1,
                'application_deadline' => now()->addDays(mt_rand(10, 30)), 'status' => 'published', 'is_featured' => in_array($n, [0, 2]),
                'featured_until' => in_array($n, [0, 2]) ? now()->addDays(20) : null,
                'published_at' => now()->subHours($daysAgo * 9 + 1), 'expires_at' => now()->addDays(30 - $daysAgo),
                'created_at' => now()->subHours($daysAgo * 9 + 2),
            ]);
            $job->skills()->sync(collect($skills)->map(fn ($s) => Skill::firstOrCreate(['slug' => Str::slug($s)], ['name' => $s, 'category_id' => $cats[$cat]->id])->id));
            foreach (QuestionTemplates::forCategory($cat) as $qi => $q) {
                $job->questions()->create($q + ['sort_order' => $qi]);
            }
            $jobModels->push($job);
        }
        // a draft job for the demo client
        JobPost::forceCreate(['id' => (string) Str::ulid(), 'client_profile_id' => $clients[0]->id, 'category_id' => $cats['photography']->id, 'title' => 'Menu photography for new branch', 'description' => 'Shoot 30 menu items for our Bole branch opening. Natural light, consistent props, delivered edited.', 'budget_type' => 'fixed', 'budget_min_minor' => 1500000, 'budget_max_minor' => 2500000, 'currency' => 'ETB', 'experience_level' => 'intermediate', 'status' => 'draft']);

        // --- Applications ---
        $answerFor = function ($q) {
            return match ($q->type) {
                'number' => (string) mt_rand(2, 9),
                'yes_no' => 'Yes',
                'multiple_choice' => $q->options[0] ?? 'Yes',
                'multi_select' => json_encode(array_slice($q->options ?? [], 0, 2)),
                'portfolio_link' => 'https://portfolio.example.com/work',
                default => 'I start with a short discovery call, share 2–3 directions within the first week, then refine one direction with two rounds of feedback before final delivery.',
            };
        };
        $statuses = ['submitted', 'viewed', 'shortlisted', 'submitted', 'rejected', 'viewed', 'discussion'];
        foreach ($jobModels as $ji => $job) {
            $pool = $freelancers->filter(fn ($f) => $f->category_id === $job->category_id || mt_rand(0, 4) === 0)->shuffle()->take(mt_rand(3, 7));
            foreach ($pool->values() as $k => $f) {
                if ($f->user->email === 'freelancer@demo.test' && $ji === 0) {
                    continue; // handled in the scripted flow below
                }
                $this->apply($job, $f, $statuses[($ji + $k) % count($statuses)], $answerFor);
            }
        }

        // --- Scripted demo flow ---
        $demoF = $freelancers->first();               // Hana — freelancer@demo.test
        $demoC = $clients->first();                   // Mikias — client@demo.test
        $svc = app(AgreementService::class);

        // 1) Hana ↔ Buna House: confirmed agreement → active project with milestones & chat
        $job = $jobModels[0];
        $app1 = $this->apply($job, $demoF, 'shortlisted', $answerFor, 'Selam! I have designed packaging for three Ethiopian coffee brands. I would build a modular label system so each roast gets its own colour while the bag stays instantly recognisable on shelf.');
        $conv = Conversation::create(['client_user_id' => $demoC->user_id, 'freelancer_user_id' => $demoF->user_id, 'job_post_id' => $job->id, 'application_id' => $app1->id, 'last_message_at' => now()]);
        $chat = [
            [$demoC->user_id, 'Hi Hana — loved the Teff & Co. project. Could you share how you would handle the 6 roast variants?'],
            [$demoF->user_id, 'Thank you! One master layout with a colour band and an illustrated origin mark per roast. I can show two directions next week.'],
            [$demoC->user_id, 'Perfect. Let\'s put it in an agreement — 3 milestones?'],
            [$demoF->user_id, 'Sounds good. I\'ve drafted it — please review version 1.'],
        ];
        foreach ($chat as $m => [$uid, $body]) {
            $conv->messages()->create(['user_id' => $uid, 'body' => $body, 'created_at' => now()->subHours(30 - $m * 5), 'read_at' => now()]);
        }
        $app1->transitionTo('discussion', $demoC->user_id, 'Client invited to chat');
        $v1 = $svc->propose($app1, $demoF->user, [
            'title' => 'Buna House packaging system', 'scope' => "Packaging redesign for 6 roast variants (250g & 500g bags).\nExcludes printing and photography.",
            'deliverables' => "Print-ready AI/PDF dielines for 12 SKUs\nLabel template system\nMini brand guide (PDF)", 'amount_minor' => 3600000, 'currency' => 'ETB',
            'deadline' => now()->addWeeks(5)->format('Y-m-d'), 'revisions' => 2, 'cancellation_terms' => 'Either party may cancel with 3 days written notice. Accepted milestones remain payable.',
            'milestones' => [
                ['title' => 'Two creative directions', 'due_date' => now()->addWeek()->format('Y-m-d'), 'amount_minor' => 1000000],
                ['title' => 'Refined system + 6 labels', 'due_date' => now()->addWeeks(3)->format('Y-m-d'), 'amount_minor' => 1600000],
                ['title' => 'Print-ready files & guide', 'due_date' => now()->addWeeks(5)->format('Y-m-d'), 'amount_minor' => 1000000],
            ],
        ]);
        $svc->confirm($v1, $demoF->user, $v1->content_hash);
        $project = $svc->confirm($v1->fresh(), $demoC->user, $v1->content_hash);
        $ms = $project->milestones;
        $ms[0]->transitionTo('in_progress', $demoF->user_id);
        $ms[0]->transitionTo('submitted', $demoF->user_id);
        $ms[0]->transitionTo('accepted', $demoC->user_id);
        $ms[1]->transitionTo('in_progress', $demoF->user_id);
        $project->log('milestone', 'Mikias Fikru marked “Two creative directions” as accepted', $demoC->user_id);

        // 2) Hana applied to Gojo Tech's banking app → agreement v2 awaiting Hana's confirmation
        $app2 = $this->apply($jobModels[2], $demoF, 'shortlisted', $answerFor);
        Conversation::create(['client_user_id' => $jobModels[2]->client->user_id, 'freelancer_user_id' => $demoF->user_id, 'job_post_id' => $jobModels[2]->id, 'application_id' => $app2->id, 'last_message_at' => now()->subHour()])
            ->messages()->create(['user_id' => $jobModels[2]->client->user_id, 'body' => 'We updated the deadline in v2 — could you confirm?']);
        $terms = ['title' => 'Wallet app UI refresh', 'scope' => '25 core screens, components library, Amharic-first type scale.', 'deliverables' => 'Figma file with components and prototypes', 'amount_minor' => 9500000, 'currency' => 'ETB', 'deadline' => now()->addWeeks(8)->format('Y-m-d'), 'revisions' => 3, 'cancellation_terms' => null, 'milestones' => null];
        $svc->propose($app2, $jobModels[2]->client->user, $terms);
        $v2 = $svc->propose($app2, $jobModels[2]->client->user, array_merge($terms, ['deadline' => now()->addWeeks(9)->format('Y-m-d')]));
        $svc->confirm($v2, $jobModels[2]->client->user, $v2->content_hash);

        // 3) Pending invitation for Hana
        Invitation::forceCreate(['id' => (string) Str::ulid(), 'job_post_id' => $jobModels[10]->id, 'freelancer_profile_id' => $demoF->id, 'message' => 'Your Teff & Co. work is exactly the warmth we want. Interested in a quick chat?', 'respond_by' => now()->addDays(5), 'status' => 'pending']);

        // 4) Completed past projects with reviews (earned reputation)
        foreach ($freelancers->take(8) as $idx => $f) {
            $cl = $clients[$idx % $clients->count()];
            $pastJob = JobPost::forceCreate(['id' => (string) Str::ulid(), 'client_profile_id' => $cl->id, 'category_id' => $f->category_id, 'title' => 'Past project: '.($projectTitles[$f->category->slug][0] ?? 'Creative work'), 'description' => 'Completed engagement.', 'budget_type' => 'fixed', 'budget_max_minor' => 2000000, 'currency' => 'ETB', 'experience_level' => 'intermediate', 'status' => 'filled', 'published_at' => now()->subMonths(3)]);
            $pa = $this->apply($pastJob, $f, 'shortlisted', $answerFor);
            $v = $svc->propose($pa, $cl->user, ['title' => $pastJob->title, 'scope' => 'As discussed', 'deliverables' => 'Final files', 'amount_minor' => 2000000, 'currency' => 'ETB', 'deadline' => now()->addDays(10)->format('Y-m-d'), 'revisions' => 2, 'cancellation_terms' => null, 'milestones' => null]);
            $svc->confirm($v, $cl->user, $v->content_hash);
            $p = $svc->confirm($v->fresh(), $f->user, $v->content_hash);
            foreach (['submitted_for_review', 'delivered'] as $s) {
                $p->transitionTo($s, $f->user_id);
            }
            $p->transitionTo('completed', $cl->user_id);
            $p->forceFill(['completed_at' => now()->subMonths(2), 'created_at' => now()->subMonths(3)])->save();
            Review::create(['project_id' => $p->id, 'reviewer_id' => $cl->user_id, 'reviewee_id' => $f->user_id, 'rating' => [5, 5, 4, 5, 4, 5, 5, 4][$idx], 'body' => ['Outstanding work and very clear communication throughout.', 'Delivered ahead of schedule, would hire again.', 'Great ideas; one revision round took a little longer than planned.', 'Professional, thoughtful and easy to work with.', 'Solid result and responsive to feedback.', 'Exceeded our expectations.', 'Fantastic attention to detail.', 'Good work overall.'][$idx]]);
            Review::create(['project_id' => $p->id, 'reviewer_id' => $f->user_id, 'reviewee_id' => $cl->user_id, 'rating' => 5, 'body' => 'Clear brief and respectful of agreed terms.']);
        }

        // --- Likes ("appreciations") on portfolio projects ---
        $likers = User::where('email', 'like', '%@demo.test')->where('email', '!=', 'admin@demo.test')->pluck('id');
        foreach (PortfolioProject::with('profile')->get() as $proj) {
            $proj->likes()->sync($likers->reject(fn ($id) => $id === $proj->profile->user_id)->shuffle()->take(mt_rand(0, 12))->all());
        }

        // --- Private saves (each demo member's Collection) ---
        foreach (User::whereIn('id', $likers)->get() as $saver) {
            $saver->savedProjects()->sync(PortfolioProject::whereHas('profile', fn ($q) => $q->where('user_id', '!=', $saver->id))
                ->where('status', 'published')->inRandomOrder()->take(mt_rand(3, 9))->pluck('id')->all());
        }

        // --- A couple of paid orders (platform fees only) ---
        $std = Product::where('code', 'job_post_standard')->first();
        foreach ($jobModels->take(6) as $job) {
            \App\Models\Order::forceCreate(['id' => (string) Str::ulid(), 'user_id' => $job->client->user_id, 'product_id' => $std->id, 'job_post_id' => $job->id, 'amount_minor' => $std->price_minor, 'currency' => 'ETB', 'status' => 'paid', 'provider' => 'sandbox', 'provider_reference' => 'sbx_'.Str::lower(Str::random(20)), 'idempotency_key' => (string) Str::ulid(), 'paid_at' => $job->published_at, 'created_at' => $job->published_at]);
        }

        // --- Real notifications for the demo accounts ---
        Notification::swap($realNotifications);
        $n = fn ($u, $t, $b, $url, $icon) => $u->notify(new \App\Notifications\MarketplaceNotification($t, $b, $url, $icon));
        $n($demoF->user, 'New invitation', 'Hiwot Desta invited you to discuss “Logo & Brand Kit for Tutoring Startup”.', route('invitations.index'), 'sparkles');
        $n($demoF->user, 'Agreement awaiting your confirmation', 'Saron Yilma prepared agreement v2 for “UI Designer for Mobile Banking App Refresh”.', route('applications.show', $app2), 'file');
        $n($demoF->user, 'Milestone accepted', 'Mikias Fikru accepted “Two creative directions”.', route('projects.show', $project), 'check');
        $n($demoF->user, 'New message', 'Saron Yilma sent you a message.', route('messages.index'), 'message');
        $n($demoC->user, 'New application', 'Dawit Bekele applied to “Website Designer for Café Chain”.', route('client.jobs.applicants', $jobModels[9]), 'file');
        $n($demoC->user, 'Project agreement confirmed', '“Buna House packaging system” is now active.', route('projects.show', $project), 'check');
        $n($demoC->user, 'Payment confirmed', 'Your purchase of Standard job post is active.', route('billing.index'), 'dollar');
    }

    private function user(string $name, string $email, ?string $role = null): User
    {
        $u = User::forceCreate([
            'name' => $name, 'email' => $email, 'password' => bcrypt('password'), 'email_verified_at' => now(),
            'is_freelancer' => $role === 'freelancer', 'is_client' => $role === 'client', 'active_workspace' => $role,
            'created_at' => now()->subDays(mt_rand(30, 400)),
        ]);

        return $u;
    }

    private function apply(JobPost $job, FreelancerProfile $f, string $status, callable $answerFor, ?string $message = null): Application
    {
        $a = new Application([
            'cover_message' => $message ?? "Hello! I'm {$f->user->name}. I've done similar work for local brands and would love to help with “{$job->title}”. I'd start with a short call to align on goals, then share first concepts within a week.",
            'proposed_amount_minor' => (int) round(($job->budget_max_minor ?: 2000000) * (mt_rand(75, 100) / 100) / 10000) * 10000,
            'currency' => 'ETB', 'estimated_days' => mt_rand(7, 35), 'availability' => ['Can start immediately', 'Available from next week', 'Part-time, 20 hrs/week'][mt_rand(0, 2)],
        ]);
        $a->job_post_id = $job->id;
        $a->freelancer_profile_id = $f->id;
        $a->status = 'submitted';
        $a->created_at = min(now(), $job->published_at?->copy()->addHours(mt_rand(1, 30)) ?? now());
        $a->save();
        foreach ($job->questions as $q) {
            $a->answers()->create(['screening_question_id' => $q->id, 'answer' => $answerFor($q)]);
        }
        $a->portfolioProjects()->sync($f->publishedProjects()->take(2)->pluck('id'));
        $path = ['viewed' => ['viewed'], 'shortlisted' => ['viewed', 'shortlisted'], 'discussion' => ['viewed', 'discussion'], 'rejected' => ['viewed', 'rejected']][$status] ?? [];
        foreach ($path as $s) {
            $a->transitionTo($s, $job->client->user_id);
        }

        return $a;
    }

    private function portfolio(FreelancerProfile $profile, Category $cat, string $title, int $k): void
    {
        $seed = $this->art++;
        $cover = ArtGenerator::make("seed/{$seed}-cover.jpg", $seed * 7 + $k);
        $p = PortfolioProject::forceCreate([
            'id' => (string) Str::ulid(), 'freelancer_profile_id' => $profile->id, 'category_id' => $cat->id, 'title' => $title,
            'summary' => 'A '.strtolower($cat->name).' project focused on clarity, cultural relevance and practical results.',
            'cover_path' => $cover, 'cover_alt' => $title.' cover', 'tags' => [$cat->name, 'Ethiopia', ['Branding', 'Print', 'Digital', 'Campaign'][$k % 4]],
            'project_date' => now()->subMonths(mt_rand(1, 24)), 'status' => 'published', 'published_at' => now()->subDays(mt_rand(1, 120)),
            'views' => mt_rand(20, 1500), 'sort_order' => $k,
        ]);
        $a = ArtGenerator::make("seed/{$seed}-a.jpg", $seed * 13 + 1);
        $b = ArtGenerator::make("seed/{$seed}-b.jpg", $seed * 17 + 3);
        $blocks = [
            ['type' => 'heading', 'body' => 'The brief'],
            ['type' => 'text', 'body' => 'The client needed a fresh direction that felt modern while staying true to its Ethiopian roots.'],
            ['type' => 'image', 'media_path' => $a, 'caption' => 'Early exploration', 'alt' => 'Exploration sketches'],
            ['type' => 'image_text', 'media_path' => $b, 'alt' => 'Final design applied', 'body' => "Process & outcome\nWe ran two rounds of concepts, tested them with real customers and refined the strongest route into a complete system."],
            ['type' => 'quote', 'body' => 'They understood our customers better than we expected. The new look paid for itself within a season.', 'caption' => 'Client feedback'],
            ['type' => 'gallery', 'media' => [['path' => $a, 'alt' => null], ['path' => $b, 'alt' => null]], 'caption' => 'Final applications'],
        ];
        foreach ($blocks as $i => $block) {
            $p->blocks()->create($block + ['sort_order' => $i, 'settings' => \App\Models\PortfolioBlock::cleanSettings($block['type'], [])]);
        }
    }

    /** A real case study from database/seeders/demo-images/<author>/<slug>/manifest.json. */
    private function realPortfolio(FreelancerProfile $profile, Category $cat, string $slug, int $k): void
    {
        $dir = database_path("seeders/demo-images/{$slug}");
        $m = json_decode(file_get_contents("{$dir}/manifest.json"), true);
        $disk = Storage::disk(\App\Support\Media::disk());
        $n = 0;
        $put = function (string $file) use ($dir, $disk, $slug) {
            $key = "seed/real/{$slug}/{$file}"; // $slug is "<author>/<project>"
            if (! $disk->exists($key)) {
                $disk->put($key, file_get_contents("{$dir}/{$file}"));
            }

            return $key;
        };
        $alt = function () use ($m, &$n) {
            $n++;

            return "{$m['title']} — ".strtolower($m['scope'][0] ?? 'design')." work, image {$n}";
        };

        $p = PortfolioProject::forceCreate([
            'id' => (string) Str::ulid(), 'freelancer_profile_id' => $profile->id, 'category_id' => $cat->id,
            'title' => $m['title'], 'summary' => $m['summary'],
            'cover_path' => $put($m['cover']), 'cover_alt' => "{$m['title']} brand identity",
            'tags' => $m['scope'], 'client_attribution' => $m['client'] ? Str::title(Str::lower($m['client'])) : null,
            'project_date' => $m['year'] ? Carbon::create((int) $m['year'], 6, 1) : now()->subMonths(6),
            'status' => 'published', 'published_at' => now()->subDays(mt_rand(2, 30)),
            'views' => mt_rand(400, 2500), 'sort_order' => $k,
        ]);

        $blocks = [];
        if (! empty($m['overview'])) {
            $blocks[] = ['type' => 'heading', 'body' => $m['overview_heading'] ?: 'Overview'];
            $blocks[] = ['type' => 'text', 'body' => $m['overview'], 'settings' => ['size' => 'lg']];
        }
        foreach ($m['blocks'] as $b) {
            $blocks[] = match ($b['type']) {
                'image' => ['type' => 'image', 'media_path' => $put($b['file']), 'alt' => $alt()],
                'gallery' => ['type' => 'gallery', 'media' => array_map(fn ($f) => ['path' => $put($f), 'alt' => $alt()], $b['files']), 'settings' => ['cols' => '2', 'gap' => 's']],
                default => ['type' => $b['type'], 'body' => $b['body']],
            };
        }
        if (! empty($m['deliverables'])) {
            $blocks[] = ['type' => 'heading', 'body' => 'What was delivered', 'settings' => ['pad' => 'm']];
            $blocks[] = ['type' => 'text', 'body' => collect($m['deliverables'])->map(fn ($d) => Str::title(Str::lower($d['title']))."\n".$d['desc'])->implode("\n\n")];
        }
        foreach ($blocks as $i => $block) {
            $settings = \App\Models\PortfolioBlock::cleanSettings($block['type'], $block['settings'] ?? []);
            unset($block['settings']);
            $p->blocks()->create($block + ['sort_order' => $i, 'settings' => $settings]);
        }
    }

    private function taxonomy(): array
    {
        $cats = [
            ['Brand Identity', 'brand-identity', 'pen', 'Visually and emotionally communicate a brand\'s values and personality.'],
            ['UI/UX Design', 'ui-ux-design', 'monitor', 'Crafting intuitive apps and products people enjoy using.'],
            ['Illustration', 'illustration', 'pencil-ruler', 'Books, editorial, murals and characters with a distinct voice.'],
            ['Video & Animation', 'video-animation', 'video', 'Visually engaging motion, explainers and edits that move people.'],
            ['Photography', 'photography', 'camera', 'Product, food and editorial imagery that sells the story.'],
            ['Web Design', 'web-design', 'layers', 'Responsive websites and landing pages that convert.'],
            ['Graphic Design', 'graphic-design', 'palette', 'Posters, social media and print collateral.'],
            ['Copywriting', 'copywriting', 'type', 'Strategize, create and manage engaging content across platforms.'],
        ];
        $out = [];
        foreach ($cats as $i => [$name, $slug, $icon, $desc]) {
            $out[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'icon' => $icon, 'description' => $desc, 'sort_order' => $i]);
        }

        return $out;
    }

    private function products(): void
    {
        $rows = [
            ['job_post_standard', 'client', 'Standard job post', 'Publish one job for 30 days', 150000, 30, ['Listed on the job board for 30 days', 'Screening-question builder & templates', 'Applicant comparison dashboard', 'Shortlist, chat & agreements', 'Invite freelancers directly']],
            ['job_post_featured', 'client', 'Featured job post', 'Standard listing plus top placement', 350000, 30, ['Everything in Standard', 'Featured badge & top placement', 'Highlighted in category pages', 'Shown on the home page', 'Labelled as featured — no hire guaranteed']],
            ['job_feature_boost', 'client', 'Feature boost', 'Add featured placement to a live job', 120000, 14, ['Featured badge for 14 days', 'Top of search results', 'Clearly labelled promotion']],
            ['freelancer_professional', 'freelancer', 'Professional', 'Room for your whole body of work', 45000, 30, [config('marketplace.portfolio_limits.professional').' portfolio case studies', 'Higher media allowance', 'Detailed profile & project analytics', 'Everything in Free']],
            ['freelancer_featured', 'freelancer', 'Featured placement', 'Time-limited promotional visibility', 90000, 14, ['Top of directory for 14 days', '“Sponsored” label on your card', 'Shown on the home page', 'Never guarantees work or invitations']],
        ];
        foreach ($rows as $i => [$code, $aud, $name, $tag, $price, $days, $benefits]) {
            Product::firstOrCreate(['code' => $code], ['audience' => $aud, 'name' => $name, 'tagline' => $tag, 'price_minor' => $price, 'currency' => 'ETB', 'duration_days' => $days, 'benefits' => $benefits, 'sort_order' => $i]);
        }
    }
}
