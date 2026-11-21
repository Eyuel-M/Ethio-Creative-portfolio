<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Builds a member's personal-data export, and erases an account on request. */
class PersonalData
{
    /** Writes a JSON export to private storage and returns its path. */
    public static function export(User $user): string
    {
        $user->load(['freelancerProfile.skills', 'freelancerProfile.portfolioProjects.blocks', 'clientProfile']);
        $fp = $user->freelancerProfile;
        $cp = $user->clientProfile;
        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['id', 'name', 'email', 'status', 'is_freelancer', 'is_client', 'timezone', 'created_at', 'last_login_at']),
            'freelancer_profile' => $fp ? $fp->only(['title', 'bio', 'location', 'availability', 'experience_level', 'years_experience', 'hourly_rate_minor', 'currency', 'languages', 'tools', 'website', 'contact_phone', 'contact_email', 'plan']) + ['skills' => $fp->skills->pluck('name')] : null,
            'portfolio' => $fp ? $fp->portfolioProjects->map(fn ($p) => $p->only(['id', 'title', 'summary', 'status', 'published_at', 'tags', 'client_attribution']) + [
                'cover' => Media::url($p->cover_path), 'blocks' => $p->blocks->map(fn ($b) => ['type' => $b->type, 'text' => $b->body, 'image' => Media::url($b->media_path), 'caption' => $b->caption])->all(),
            ])->all() : [],
            'client_profile' => $cp?->only(['type', 'company_name', 'industry', 'website', 'location', 'contact_phone', 'is_verified']),
            'jobs_posted' => $cp ? DB::table('job_posts')->where('client_profile_id', $cp->id)->get(['id', 'title', 'description', 'status', 'created_at']) : [],
            'applications' => $fp ? DB::table('applications')->where('freelancer_profile_id', $fp->id)->get(['id', 'job_post_id', 'cover_message', 'proposed_amount_minor', 'currency', 'status', 'created_at']) : [],
            'messages_sent' => DB::table('messages')->where('user_id', $user->id)->get(['conversation_id', 'body', 'created_at']),
            'reviews_given' => DB::table('reviews')->where('reviewer_id', $user->id)->get(['project_id', 'rating', 'body', 'created_at']),
            'reviews_received' => DB::table('reviews')->where('reviewee_id', $user->id)->get(['project_id', 'rating', 'body', 'created_at']),
            'orders' => DB::table('orders')->where('user_id', $user->id)->get(['id', 'product_id', 'amount_minor', 'currency', 'status', 'created_at', 'paid_at']),
            'site_visits' => DB::table('analytics_sessions')->where('user_id', $user->id)->get(['started_at', 'landing_path', 'source', 'source_detail', 'device', 'pageviews']),
            'activity' => DB::table('analytics_events')->where('user_id', $user->id)->get(['name', 'subject_type', 'created_at']),
        ];
        $path = 'data-exports/'.$user->id.'-'.Str::random(24).'.json';
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * Removes personal data and closes the account. Financial records (orders) and agreements stay, attached to an
     * anonymous account, because they're needed for accounting and dispute history.
     */
    public static function erase(User $user): void
    {
        DB::transaction(function () use ($user) {
            if ($fp = $user->freelancerProfile) {
                foreach ($fp->portfolioProjects()->with('blocks')->get() as $p) {
                    Media::delete($p->cover_path);
                    foreach ($p->blocks as $b) {
                        Media::delete($b->media_path);
                        foreach (array_column($b->media ?? [], 'path') as $g) {
                            Media::delete($g);
                        }
                    }
                    $p->delete();
                }
                Media::delete($fp->cover_path);
                $fp->paymentDetails()?->delete();
                $fp->forceFill(['is_public' => false, 'bio' => null, 'contact_phone' => null, 'contact_email' => null, 'website' => null, 'cover_path' => null, 'title' => 'Deleted member', 'slug' => 'deleted-'.$user->id])->save();
            }
            $user->clientProfile?->forceFill(['company_name' => null, 'contact_phone' => null, 'website' => null, 'logo_path' => null])->save();
            Media::delete($user->avatar_path);
            DB::table('messages')->where('user_id', $user->id)->update(['body' => '[deleted]']);
            DB::table('analytics_sessions')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('analytics_pageviews')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('analytics_events')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill([
                'name' => 'Deleted member', 'email' => 'deleted-'.$user->id.'@deleted.invalid', 'password' => Hash::make(Str::random(40)),
                'avatar_path' => null, 'status' => 'deleted', 'remember_token' => null, 'admin_role' => null,
            ])->save();
        });
    }
}
