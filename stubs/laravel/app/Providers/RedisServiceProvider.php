<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class RedisServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Default API rate limit using Redis store
        RateLimiter::for('api', function ($request) {
            // Identify by user id or IP
            $key = optional($request->user())->id ?: $request->ip();
            return [
                Limit::perMinute(60)->by($key)->response(function () {
                    return response()->json([
                        'message' => 'Too Many Requests',
                        'request_id' => (string) Str::uuid(),
                    ], 429);
                }),
            ];
        });
    }
}

