<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

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
