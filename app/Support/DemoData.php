<?php

namespace App\Support;

use App\Models\AgreementVersion;
use App\Models\Entitlement;
use App\Models\Order;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Placeholder content lives on accounts with an @demo.test email. Clearing removes those accounts and
 * everything that hangs off them (profiles, portfolios, jobs, applications, projects, reviews, messages,
 * likes, orders) while leaving real accounts and their own content untouched.
 */
class DemoData
{
    public const DOMAIN = '@demo.test';

    public static function users()
    {
        return User::where('email', 'like', '%'.self::DOMAIN);
    }

    public static function present(): bool
    {
        return self::users()->exists();
    }

    /** Fully loaded = placeholder creatives with published work AND live placeholder jobs. */
    public static function complete(): bool
    {
        $ids = self::users()->pluck('id');

        return \App\Models\PortfolioProject::where('status', 'published')->whereHas('profile', fn ($q) => $q->whereIn('user_id', $ids))->count() >= 10
            && \App\Models\JobPost::live()->whereHas('client', fn ($q) => $q->whereIn('user_id', $ids))->count() >= 5;
    }

    /** @return array<string,int|string> */
    public static function status(): array
    {
        $ids = self::users()->pluck('id');

        return [
            'Placeholder accounts' => $ids->count(),
            'Placeholder creatives with work' => \App\Models\FreelancerProfile::whereIn('user_id', $ids)->has('publishedProjects')->count(),
            'Placeholder projects' => \App\Models\PortfolioProject::whereHas('profile', fn ($q) => $q->whereIn('user_id', $ids))->count(),
            'Placeholder live jobs' => \App\Models\JobPost::live()->whereHas('client', fn ($q) => $q->whereIn('user_id', $ids))->count(),
            'All public creatives' => \App\Models\FreelancerProfile::public()->count(),
            'All published projects' => \App\Models\PortfolioProject::discoverable()->count(),
            'All live jobs' => \App\Models\JobPost::live()->count(),
            'Real accounts' => \App\Models\User::where('email', 'not like', '%'.self::DOMAIN)->count(),
            'State' => ! $ids->count() ? 'not loaded' : (self::complete() ? 'loaded' : 'INCOMPLETE — run: php artisan demo:seed --fresh'),
        ];
    }

    /** @return array<string,int> counts of what was removed */
    public static function clear(): array
    {
        $ids = self::users()->pluck('id');
        if ($ids->isEmpty()) {
            return ['users' => 0];
        }

        $counts = DB::transaction(function () use ($ids) {
            $either = fn ($q, $a, $b) => $q->whereIn($a, $ids)->orWhereIn($b, $ids);

            // Rows that reference users without cascading go first, in dependency order.
            $projects = Project::where(fn ($q) => $either($q, 'client_user_id', 'freelancer_user_id'))->get();
            foreach ($projects as $p) {
                foreach ($p->files as $f) {
                    Storage::disk($f->disk)->delete($f->object_key);
                }
                $p->delete(); // cascades milestones, events, files, reviews
            }
            $reviews = Review::where(fn ($q) => $either($q, 'reviewer_id', 'reviewee_id'))->delete();
            $agreements = AgreementVersion::whereIn('created_by', $ids)->delete();
            Entitlement::whereIn('user_id', $ids)->delete();
            $orders = Order::whereIn('user_id', $ids)->delete();
            DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $ids)->delete();

            // Deleting the accounts cascades to profiles → portfolios/likes, jobs → applications,
            // invitations, conversations/messages, saved items, confirmations and reports.
            DB::table('data_requests')->whereIn('user_id', $ids)->delete();
            $users = User::whereIn('id', $ids)->delete();

            return ['users' => $users, 'projects' => $projects->count(), 'reviews' => $reviews, 'agreements' => $agreements, 'orders' => $orders];
        });

        Storage::disk('public')->deleteDirectory('seed');
        foreach (['analytics_events', 'analytics_pageviews', 'analytics_sessions'] as $t) {
            DB::table($t)->where('is_demo', true)->delete();
        }

        return $counts;
    }
}
