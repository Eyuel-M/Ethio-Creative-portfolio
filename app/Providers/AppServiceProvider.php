<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\SandboxGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, SandboxGateway::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(8)->by($r->ip()));
        RateLimiter::for('messages', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perMinute(20)->by($r->user()?->id ?: $r->ip()));

        View::composer(['components.layouts.app', 'dashboard.*'], function ($view) {
            $user = auth()->user();
            if (! $user) {
                return;
            }
            $view->with('navCounts', [
                'notifications' => $user->unreadNotifications()->count(),
                'messages' => Conversation::for($user)->whereHas('messages', fn ($m) => $m->whereNull('read_at')->where('user_id', '!=', $user->id))->count(),
            ]);
        });
    }
}
