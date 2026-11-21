<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\FreelancerProfile;
use App\Models\JobPost;
use App\Models\PortfolioProject;
use App\Models\Skill;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Categories & skills, homepage featuring and the site-wide announcement. */
class ContentController extends Controller
{
    public const ICONS = ['palette', 'pencil-ruler', 'monitor', 'image', 'camera', 'video', 'type', 'music', 'megaphone', 'layers', 'box', 'sparkles', 'briefcase', 'pen'];

    public function categories()
    {
        $categories = Category::withCount(['skills'])->orderBy('sort_order')->get();
        $use = [
            'freelancers' => DB::table('freelancer_profiles')->selectRaw('category_id, COUNT(*) n')->groupBy('category_id')->pluck('n', 'category_id'),
            'jobs' => DB::table('job_posts')->selectRaw('category_id, COUNT(*) n')->groupBy('category_id')->pluck('n', 'category_id'),
            'projects' => DB::table('portfolio_projects')->selectRaw('category_id, COUNT(*) n')->groupBy('category_id')->pluck('n', 'category_id'),
        ];
        $skills = Skill::orderBy('name')->get()->groupBy('category_id');

        return view('admin.content.categories', ['categories' => $categories, 'use' => $use, 'skills' => $skills, 'icons' => self::ICONS]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60|unique:categories,name', 'icon' => ['nullable', Rule::in(self::ICONS)], 'description' => 'nullable|string|max:200']);
        $cat = Category::create($data + ['slug' => Str::slug($data['name']), 'sort_order' => (int) Category::max('sort_order') + 1]);
        AuditLog::record('category.created', $cat);

        return back()->with('status', "Category “{$cat->name}” created. It now shows in filters, sign-up and job forms.");
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('categories', 'name')->ignore($category->id)], 'icon' => ['nullable', Rule::in(self::ICONS)],
            'description' => 'nullable|string|max:200', 'sort_order' => 'nullable|integer|min:0|max:999',
        ]);
        $category->update($data);
        AuditLog::record('category.updated', $category);

        return back()->with('status', 'Category updated.');
    }

    public function destroyCategory(Category $category)
    {
        $used = DB::table('freelancer_profiles')->where('category_id', $category->id)->exists() || DB::table('job_posts')->where('category_id', $category->id)->exists()
            || DB::table('portfolio_projects')->where('category_id', $category->id)->exists();
        abort_if($used, 422, 'This category is in use. Rename it instead, or move its members first.');
        AuditLog::record('category.deleted', $category, null, ['name' => $category->name]);
        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    public function storeSkill(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60', 'category_id' => 'nullable|exists:categories,id']);
        $skill = Skill::firstOrCreate(['slug' => Str::slug($data['name'])], ['name' => $data['name'], 'category_id' => $data['category_id'] ?? null]);
        AuditLog::record('skill.created', $skill);

        return back()->with('status', "Skill “{$skill->name}” added.");
    }

    public function destroySkill(Skill $skill)
    {
        AuditLog::record('skill.deleted', $skill, null, ['name' => $skill->name]);
        $skill->delete(); // pivot rows cascade

        return back()->with('status', 'Skill removed from the list and from profiles and jobs.');
    }

    public function featured()
    {
        $projects = PortfolioProject::with('profile.user')->whereNotNull('featured_at')->where('status', 'published')->latest('featured_at')->get();
        $talent = FreelancerProfile::with('user')->where('featured_until', '>', now())->orderBy('featured_until')->get();
        $jobs = JobPost::with('client')->where('is_featured', true)->where(fn ($q) => $q->whereNull('featured_until')->orWhere('featured_until', '>', now()))->get();
        $candidates = PortfolioProject::with('profile.user')->where('status', 'published')->whereNull('featured_at')
            ->withCount('likes')->orderByDesc('likes_count')->orderByDesc('views')->take(8)->get();

        return view('admin.content.featured', compact('projects', 'talent', 'jobs', 'candidates'));
    }

    public function announcement()
    {
        return view('admin.content.announcement', ['a' => Settings::get('announcement')]);
    }

    public function updateAnnouncement(Request $request)
    {
        $data = $request->validate([
            'active' => 'nullable|boolean', 'text' => 'required_if:active,1|nullable|string|max:200', 'link' => 'nullable|url|max:300',
            'link_label' => 'nullable|string|max:40', 'tone' => 'required|in:info,success,warning', 'audience' => 'required|in:all,guests,members,freelancers,clients',
        ]);
        $data['active'] = $request->boolean('active');
        Settings::set('announcement', $data);
        AuditLog::record('announcement.updated', null, $data['active'] ? $data['text'] : 'Turned off');

        return back()->with('status', $data['active'] ? 'Announcement is live.' : 'Announcement turned off.');
    }
}
