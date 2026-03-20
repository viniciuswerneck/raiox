<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
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

    public function boot(): void
    {
        RateLimiter::for('gemini-api', function ($job) {
            // Permite 15 requisições por minuto na API do Gemini.
            // Cota free tier é geralmente 15 RPM.
            return Limit::perMinute(15);
        });
    }
}
