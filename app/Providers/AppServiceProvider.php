<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Rate limit komunitas per akun (§03 pagar pengaman).
        RateLimiter::for('community-post', function (Request $request) {
            $perMinute = (int) config('lentera.rate.posts_per_minute', 6);

            return Limit::perMinute($perMinute)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('community-react', function (Request $request) {
            $perMinute = (int) config('lentera.rate.reactions_per_minute', 30);

            return Limit::perMinute($perMinute)->by($request->user()?->id ?: $request->ip());
        });
    }
}
