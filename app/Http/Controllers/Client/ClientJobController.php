<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\JobPost;
use App\Models\Product;
use App\Models\ScreeningQuestion;
use App\Models\Skill;
use App\Services\Payments\OrderService;
use App\Support\Money;
use App\Support\QuestionTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientJobController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $base = $request->user()->clientProfile->jobPosts();
        $jobs = (clone $base)->when($status, fn ($q) => $q->where('status', $status))
            ->withCount(['applications', 'applications as new_applications_count' => fn ($q) => $q->where('status', 'submitted'),
                'applications as shortlisted_count' => fn ($q) => $q->where('status', 'shortlisted')])
            ->with('category')->latest()->paginate(10)->withQueryString();
        $counts = (clone $base)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('client.jobs.index', compact('jobs', 'counts', 'status'));
    }

    public function create()
    {
        return view('client.jobs.form', $this->formData(new JobPost(['currency' => 'ETB', 'budget_type' => 'fixed', 'experience_level' => 'intermediate', 'hires_needed' => 1, 'is_remote' => true])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $job = DB::transaction(function () use ($request, $data) {
            $job = new JobPost($data['attributes']);
            $job->client_profile_id = $request->user()->clientProfile->id;
            $job->status = 'draft';
            $job->save();
            $this->syncRelations($job, $data);
            \App\Models\StatusHistory::create(['subject_type' => JobPost::class, 'subject_id' => $job->id, 'to_status' => 'draft', 'actor_id' => $request->user()->id]);

            return $job;
        });

        return $request->input('intent') === 'publish'
            ? redirect()->route('client.jobs.publish', $job)
            : redirect()->route('client.jobs.edit', $job)->with('status', 'Draft saved.');
    }

    public function edit(Request $request, JobPost $job)
    {
        $this->authorizeOwner($request, $job);
        $job->load(['questions', 'skills']);

        return view('client.jobs.form', $this->formData($job));
    }

    public function update(Request $request, JobPost $job)
    {
        $this->authorizeOwner($request, $job);
        abort_if(in_array($job->status, ['filled', 'closed', 'removed'], true), 403, 'Closed jobs cannot be edited.');
        $data = $this->validated($request);
        // Screening questions are locked once applications exist so answers stay comparable.
        $lockQuestions = $job->applications()->exists();
        DB::transaction(function () use ($job, $data, $lockQuestions) {
            $job->update($data['attributes']);
            $this->syncRelations($job, $data, $lockQuestions);
        });

        return $request->input('intent') === 'publish' && in_array($job->status, ['draft', 'pending_payment', 'expired'], true)
            ? redirect()->route('client.jobs.publish', $job)
            : back()->with('status', $lockQuestions ? 'Job updated. Screening questions are locked because applications exist.' : 'Job updated.');
    }

    public function publish(Request $request, JobPost $job)
    {
        $this->authorizeOwner($request, $job);
        abort_unless(in_array($job->status, ['draft', 'pending_payment', 'expired'], true), 404);
        $products = Product::where('audience', 'client')->where('is_active', true)->whereIn('code', ['job_post_standard', 'job_post_featured'])->orderBy('sort_order')->get();
        $job->load(['questions', 'skills', 'category']);

        return view('client.jobs.publish', compact('job', 'products'));
    }

    public function checkout(Request $request, JobPost $job, OrderService $orders)
    {
        $this->authorizeOwner($request, $job);
        $data = $request->validate(['product' => 'required|in:job_post_standard,job_post_featured,job_feature_boost']);
        $product = Product::where('code', $data['product'])->where('is_active', true)->firstOrFail();

        if ($product->code === 'job_feature_boost') {
            abort_unless($job->status === 'published', 422);
        } else {
            abort_unless(in_array($job->status, ['draft', 'pending_payment', 'expired'], true), 422);
            if ($job->status !== 'pending_payment') {
                $job->transitionTo('pending_payment', $request->user()->id, 'Checkout started');
            }
        }

        $order = $orders->create($request->user(), $product, $job);

        return redirect()->away($orders->checkoutUrl($order));
    }

    public function status(Request $request, JobPost $job)
    {
        $this->authorizeOwner($request, $job);
        $data = $request->validate(['status' => 'required|in:paused,published,closed,filled', 'reason' => 'nullable|string|max:300']);
        if ($data['status'] === 'published') {
            abort_unless($job->status === 'paused' && ($job->expires_at === null || $job->expires_at->isFuture()), 422);
        }
        $job->transitionTo($data['status'], $request->user()->id, $data['reason'] ?? null);

        return back()->with('status', 'Job is now '.$data['status'].'.');
    }

    public function applicants(Request $request, JobPost $job)
    {
        $this->authorizeOwner($request, $job);
        $f = $request->validate([
            'status' => 'nullable|in:'.implode(',', \App\Models\Application::STATUSES),
            'skill' => 'nullable|string|max:60',
            'max_budget' => 'nullable|numeric|min:0',
            'level' => 'nullable|in:entry,intermediate,expert',
            'sort' => 'nullable|in:newest,price_low,price_high,fastest',
        ]);
        $job->load(['questions', 'category']);

        $q = $job->applications()
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['skill'] ?? null, fn ($q, $s) => $q->whereHas('freelancer.skills', fn ($w) => $w->where('name', 'like', "%{$s}%")))
            ->when($f['max_budget'] ?? null, fn ($q, $b) => $q->where('proposed_amount_minor', '<=', Money::toMinor($b)))
            ->when($f['level'] ?? null, fn ($q, $l) => $q->whereHas('freelancer', fn ($w) => $w->where('experience_level', $l)))
            ->with(['freelancer.user', 'freelancer.skills', 'answers', 'portfolioProjects']);
        $q = match ($f['sort'] ?? 'newest') {
            'price_low' => $q->orderBy('proposed_amount_minor'),
            'price_high' => $q->orderByDesc('proposed_amount_minor'),
            'fastest' => $q->orderBy('estimated_days'),
            default => $q->latest(),
        };
        $applications = $q->get();
        $view = $request->query('view', 'cards');
        $counts = $job->applications()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('client.jobs.applicants', compact('job', 'applications', 'f', 'view', 'counts'));
    }

    private function authorizeOwner(Request $request, JobPost $job): void
    {
        abort_unless($job->ownedBy($request->user()), 403);
    }

    private function formData(JobPost $job): array
    {
        $categories = Category::with('skills')->orderBy('sort_order')->get();
        $templates = $categories->mapWithKeys(fn ($c) => [$c->id => QuestionTemplates::forCategory($c->slug)]);

        return [
            'job' => $job,
            'categories' => $categories,
            'templates' => $templates,
            'types' => ScreeningQuestion::TYPES,
            'questions' => $job->exists ? $job->questions->map->only(['type', 'prompt', 'is_required', 'options'])->values() : collect(QuestionTemplates::common()),
            'skillNames' => $job->exists ? $job->skills->pluck('name')->implode(', ') : '',
            'allSkills' => Skill::orderBy('name')->pluck('name'),
        ];
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'title' => 'required|string|max:140',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|min:40|max:8000',
            'deliverables' => 'nullable|string|max:3000',
            'skills' => 'nullable|string|max:400',
            'budget_type' => 'required|in:fixed,hourly',
            'budget_min' => 'nullable|numeric|min:0|max:100000000',
            'budget_max' => 'nullable|numeric|min:0|max:100000000|gte:budget_min',
            'currency' => 'required|in:'.implode(',', config('marketplace.currencies')),
            'duration' => 'nullable|string|max:40',
            'deadline' => 'nullable|date|after:today',
            'experience_level' => 'required|in:entry,intermediate,expert',
            'location' => 'nullable|string|max:120',
            'is_remote' => 'nullable|boolean',
            'hires_needed' => 'required|integer|min:1|max:20',
            'application_deadline' => 'nullable|date|after_or_equal:today',
            'questions' => 'nullable|array|max:15',
            'questions.*.type' => 'required|in:'.implode(',', array_keys(ScreeningQuestion::TYPES)),
            'questions.*.prompt' => 'required|string|max:500',
            'questions.*.is_required' => 'nullable|boolean',
            'questions.*.options' => 'nullable|array|max:12',
            'questions.*.options.*' => 'nullable|string|max:120',
        ]);
        foreach ($v['questions'] ?? [] as $i => $q) {
            if (in_array($q['type'], ['multiple_choice', 'multi_select'], true) && count(array_filter($q['options'] ?? [])) < 2) {
                throw \Illuminate\Validation\ValidationException::withMessages(["questions.$i.options" => 'Choice questions need at least two options.']);
            }
        }

        return [
            'attributes' => [
                'title' => $v['title'],
                'category_id' => $v['category_id'],
                'description' => $v['description'],
                'deliverables' => $v['deliverables'] ?? null,
                'budget_type' => $v['budget_type'],
                'budget_min_minor' => Money::toMinor($v['budget_min'] ?? null),
                'budget_max_minor' => Money::toMinor($v['budget_max'] ?? null),
                'currency' => $v['currency'],
                'duration' => $v['duration'] ?? null,
                'deadline' => $v['deadline'] ?? null,
                'experience_level' => $v['experience_level'],
                'location' => $v['location'] ?? null,
                'is_remote' => (bool) ($v['is_remote'] ?? false),
                'hires_needed' => $v['hires_needed'],
                'application_deadline' => $v['application_deadline'] ?? null,
            ],
            'skills' => collect(explode(',', (string) ($v['skills'] ?? '')))->map(fn ($s) => trim($s))->filter()->unique()->take(15)->values(),
            'questions' => $v['questions'] ?? [],
        ];
    }

    private function syncRelations(JobPost $job, array $data, bool $lockQuestions = false): void
    {
        $skillIds = $data['skills']->map(fn ($name) => Skill::firstOrCreate(
            ['slug' => \Illuminate\Support\Str::slug($name)],
            ['name' => $name, 'category_id' => $job->category_id]
        )->id);
        $job->skills()->sync($skillIds);

        if ($lockQuestions) {
            return;
        }
        $job->questions()->delete();
        foreach (array_values($data['questions']) as $i => $q) {
            $job->questions()->create([
                'type' => $q['type'],
                'prompt' => $q['prompt'],
                'is_required' => (bool) ($q['is_required'] ?? false),
                'options' => in_array($q['type'], ['multiple_choice', 'multi_select'], true) ? array_values(array_filter($q['options'] ?? [])) : null,
                'sort_order' => $i,
            ]);
        }
    }
}
