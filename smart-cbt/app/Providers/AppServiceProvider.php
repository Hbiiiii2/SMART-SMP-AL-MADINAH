<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // BLK-03 & Phase 9: User-keyed rate limiters for examination actions
        // Keyed strictly by authenticated user ID or session ID to eliminate whole-school lockout on shared NAT gateways.
        RateLimiter::for('exam-autosave', function (Request $request) {
            $key = 'autosave_' . ($request->user()?->id ?? $request->session()->getId());
            return Limit::perMinute(300)->by($key);
        });

        RateLimiter::for('exam-start', function (Request $request) {
            $key = 'start_' . ($request->user()?->id ?? $request->session()->getId());
            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('exam-submit', function (Request $request) {
            $key = 'submit_' . ($request->user()?->id ?? $request->session()->getId());
            return Limit::perMinute(30)->by($key);
        });
    }
}
