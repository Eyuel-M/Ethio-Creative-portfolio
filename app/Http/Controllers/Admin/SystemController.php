<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminCodeMail;
use App\Models\AuditLog;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/** Platform switches, system health, the audit log and admin step-up verification. */
class SystemController extends Controller
{
    public function settings()
    {
        return view('admin.system.settings', ['flags' => Settings::get('flags'), 'labels' => Settings::FLAG_LABELS]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate(['reason' => 'required|string|min:3|max:300']);
        $before = Settings::get('flags');
        $after = [];
        foreach (array_keys(Settings::DEFAULTS['flags']) as $k) {
            $after[$k] = $request->boolean("flags.{$k}");
        }
        Settings::set('flags', $after);
        $changed = array_keys(array_diff_assoc(array_map('intval', $after), array_map('intval', $before)));
        AuditLog::record('settings.updated', null, $data['reason'], ['changed' => $changed, 'flags' => $after]);

        return back()->with('status', $changed ? 'Switches updated: '.implode(', ', $changed).'.' : 'Nothing changed.');
    }

    public function health()
    {
        $disk = Storage::disk(\App\Support\Media::disk());
        $bytes = Cache::remember('admin:storage-bytes', 600, function () use ($disk) {
            $sum = 0;
            foreach (array_slice($disk->allFiles(), 0, 20000) as $f) {
                $sum += $disk->size($f);
            }

            return $sum;
        });
        $checks = [
            ['Scheduler', ($last = Cache::get('scheduler:last_run')) && now()->diffInMinutes($last) < 5, $last ? 'Last ran '.\Illuminate\Support\Carbon::parse($last)->diffForHumans() : 'Never ran — add the cron line from the README'],
            ['Failed background jobs', ($failed = DB::table('failed_jobs')->count()) === 0, $failed ? "{$failed} failed" : 'None'],
            ['Queued jobs waiting', ($queued = DB::table('jobs')->count()) < 50, "{$queued} waiting · driver: ".config('queue.default')],
            ['Unprocessed payment notifications', ($pe = DB::table('payment_events')->whereNull('processed_at')->where('created_at', '<', now()->subMinutes(10))->count()) === 0, $pe ? "{$pe} stuck" : 'All processed'],
            ['Unused uploads waiting for cleanup', ($mu = DB::table('media_uploads')->where('created_at', '<', now()->subDays(2))->count()) === 0, $mu ? "{$mu} older than 2 days — is the scheduler running?" : 'Clean'],
            ['Debug mode', ! config('app.debug') || ! app()->isProduction(), config('app.debug') ? 'On'.(app()->isProduction() ? ' in production — turn it off!' : ' (fine outside production)') : 'Off'],
            ['Image processing (GD + WebP)', function_exists('imagewebp') && function_exists('imagejpeg'), (function_exists('imagejpeg') ? 'JPEG ✓ ' : 'JPEG ✗ ').(function_exists('imagewebp') ? 'WebP ✓' : 'WebP ✗')],
            ['Photo orientation (exif)', function_exists('exif_read_data'), function_exists('exif_read_data') ? 'Available' : 'Missing — phone photos may appear sideways'],
            ['Upload limits', $this->bytes(ini_get('upload_max_filesize')) >= 20 << 20, 'upload_max_filesize '.ini_get('upload_max_filesize').' · post_max_size '.ini_get('post_max_size').' · max_file_uploads '.ini_get('max_file_uploads')],
            ['Admin two-step sign-in', (bool) config('admin.step_up') || ! app()->isProduction(), config('admin.step_up') ? 'On' : 'Off'.(app()->isProduction() ? ' — turn on ADMIN_STEP_UP' : ' (on automatically in production)')],
        ];
        $info = [
            'Environment' => app()->environment(), 'PHP' => PHP_VERSION, 'Laravel' => app()->version(), 'Database' => config('database.default'),
            'Mail' => config('mail.default'), 'Cache' => config('cache.default'), 'Media storage' => \App\Support\Media::disk().' · '.number_format($bytes / 1048576, 1).' MB',
            'Members' => number_format(DB::table('users')->count()), 'Analytics rows' => number_format(DB::table('analytics_pageviews')->count()).' page views',
            'Demo data' => \App\Support\DemoData::present() ? 'Loaded (remove with php artisan demo:clear)' : 'Not loaded',
        ];
        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->take(10)->get();

        return view('admin.system.health', compact('checks', 'info', 'failedJobs'));
    }

    public function audit(Request $request)
    {
        $logs = AuditLog::with('actor')
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->when($request->query('actor'), fn ($q, $a) => $q->where('actor_id', $a))
            ->latest('created_at')->paginate(50)->withQueryString();
        $actions = AuditLog::selectRaw('action, COUNT(*) n')->groupBy('action')->pluck('n', 'action')
            ->groupBy(fn ($n, $a) => \Illuminate\Support\Str::before($a, '.'), true)->map->sum()->sortDesc();
        $actors = \App\Models\User::whereIn('id', AuditLog::whereNotNull('actor_id')->distinct()->pluck('actor_id'))->whereNotNull('admin_role')->get();

        return view('admin.system.audit', compact('logs', 'actions', 'actors'));
    }

    /** Admins who also have a member account can browse as that member, then return to admin mode. */
    public function switchMode(Request $request)
    {
        $mode = $request->validate(['mode' => 'required|in:admin,member'])['mode'];
        abort_if($mode === 'member' && ! $request->user()->hasMemberWorkspace(), 422);
        $request->session()->put('member_mode', $mode === 'member');

        return $mode === 'member'
            ? redirect()->route('dashboard')->with('status', 'You are using the site as a member. Switch back from your avatar menu.')
            : redirect()->route('admin.overview');
    }

    // ── Step-up verification ──

    public function verify(Request $request)
    {
        $user = $request->user();
        $key = "admin-code:{$user->id}";
        if (! Cache::has($key) || $request->boolean('resend')) {
            $code = (string) random_int(100000, 999999);
            Cache::put($key, ['hash' => hash('sha256', $code), 'tries' => 0], now()->addMinutes(10));
            Mail::to($user->email)->send(new AdminCodeMail($code));
            if (! app()->isProduction()) {
                session()->flash('dev_code', $code);
            }
        }

        return view('admin.system.verify', ['email' => $user->email]);
    }

    public function confirmVerify(Request $request)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $key = "admin-code:{$request->user()->id}";
        $entry = Cache::get($key);
        if (! $entry || $entry['tries'] >= 5) {
            Cache::forget($key);

            return redirect()->route('admin.verify')->withErrors(['code' => 'That code expired. We sent a new one.']);
        }
        if (! hash_equals($entry['hash'], hash('sha256', $data['code']))) {
            Cache::put($key, ['hash' => $entry['hash'], 'tries' => $entry['tries'] + 1], now()->addMinutes(10));

            return back()->withErrors(['code' => 'That code is not right.']);
        }
        Cache::forget($key);
        $request->session()->put('admin_verified_at', now()->timestamp);
        AuditLog::record('admin.verified');

        return redirect()->intended(route('admin.overview'));
    }

    private function bytes(string $v): int
    {
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) { 'g' => $n << 30, 'm' => $n << 20, 'k' => $n << 10, default => $n };
    }
}
