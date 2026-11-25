<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Conversation;
use App\Models\JobPost;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->inAdminMode()) {
            return redirect()->route('admin.overview');
        }

        return match ($user->workspace()) {
            'client' => $this->client($request),
            'freelancer' => $this->freelancer($request),
            // Members who haven't picked a path yet: a welcome page to choose freelancer or client.
            default => view('dashboard.welcome', ['need' => $request->query('need')]),
        };
    }

    private function freelancer(Request $request)
    {
        $user = $request->user();
        $profile = $user->freelancerProfile->load(['skills', 'paymentDetails', 'user']);
        $apps = $profile->applications();
        $total = (clone $apps)->count();
        $positive = (clone $apps)->whereIn('status', ['shortlisted', 'discussion', 'negotiation', 'accepted'])->count();

        $stats = [
            'completion' => $profile->completion(),
            'applications' => $total,
            'response_rate' => $total ? (int) round((clone $apps)->where('status', '!=', 'submitted')->count() / $total * 100) : 0,
            'shortlist_rate' => $total ? (int) round($positive / $total * 100) : 0,
            'profile_views' => $profile->profile_views,
            'portfolio_views' => (int) $profile->portfolioProjects()->sum('views'),
            'published' => $profile->publishedProjects()->count(),
            'limit' => $profile->portfolioLimit(),
            'invitations' => $profile->invitations()->where('status', 'pending')->count(),
        ];
        $series = $this->monthlySeries((clone $apps)->pluck('created_at'));
        $projects = Project::for($user)->whereNotIn('status', ['completed', 'cancelled'])->with(['milestones', 'client.clientProfile', 'agreement'])->latest()->take(4)->get();
        $recentApps = (clone $apps)->with('jobPost.client.user')->latest()->take(5)->get();
        $recommended = JobPost::live()->where('category_id', $profile->category_id)
            ->whereDoesntHave('applications', fn ($q) => $q->where('freelancer_profile_id', $profile->id))
            ->with('client.user')->withCount('applications')->latest('published_at')->take(3)->get();

        return view('dashboard.freelancer', array_merge(compact('profile', 'stats', 'series', 'projects', 'recentApps', 'recommended'), $this->shared($request)));
    }

    private function client(Request $request)
    {
        $user = $request->user();
        $client = $user->clientProfile;
        $jobs = $client->jobPosts();
        $appQuery = Application::whereIn('job_post_id', (clone $jobs)->select('id'));

        $stats = [
            'active_jobs' => (clone $jobs)->where('status', 'published')->count(),
            'drafts' => (clone $jobs)->whereIn('status', ['draft', 'pending_payment'])->count(),
            'applications' => (clone $appQuery)->count(),
            'new' => (clone $appQuery)->where('status', 'submitted')->count(),
            'shortlisted' => (clone $appQuery)->where('status', 'shortlisted')->count(),
            'saved' => $user->shortlist()->count(),
            'active_projects' => Project::for($user)->whereNotIn('status', ['completed', 'cancelled'])->count(),
        ];
        $series = $this->monthlySeries((clone $appQuery)->pluck('created_at'));
        $projects = Project::for($user)->whereNotIn('status', ['completed', 'cancelled'])->with(['milestones', 'freelancer', 'agreement'])->latest()->take(4)->get();
        $recentApps = (clone $appQuery)->with(['jobPost', 'freelancer.user'])->latest()->take(5)->get();
        $openJobs = (clone $jobs)->whereIn('status', ['published', 'draft', 'pending_payment', 'paused'])->withCount('applications')->latest()->take(4)->get();

        return view('dashboard.client', array_merge(compact('client', 'stats', 'series', 'projects', 'recentApps', 'openJobs'), $this->shared($request)));
    }

    private function shared(Request $request): array
    {
        $user = $request->user();

        return [
            'activity' => $user->notifications()->take(6)->get(),
            'conversations' => Conversation::for($user)->with(['client', 'freelancer', 'latestMessage'])->orderByDesc('last_message_at')->take(3)->get(),
        ];
    }

    /** Last 12 months of counts, oldest first. */
    private function monthlySeries($dates): array
    {
        $buckets = collect(range(11, 0))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonths($i)->format('Y-m') => 0])->all();
        foreach ($dates as $d) {
            $k = Carbon::parse($d)->format('Y-m');
            if (isset($buckets[$k])) {
                $buckets[$k]++;
            }
        }

        return collect($buckets)->map(fn ($v, $k) => ['label' => Carbon::createFromFormat('Y-m', $k)->format('M'), 'month' => Carbon::createFromFormat('Y-m', $k)->format('F Y'), 'value' => $v])->values()->all();
    }
}
