<?php

namespace App\Providers;

use App\Contracts\PrizePicker;
use App\Services\WeightedPrizePicker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const HOME = '/backstage';

    public function register(): void
    {
        $this->app->bind(PrizePicker::class, WeightedPrizePicker::class);
    }

    public function boot(): void
    {
        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
