<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Scout\EngineManager;

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
        $this->configureRateLimiting();
        $this->configureScout();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('public-content-api', function (Request $request) {
            $limit = (int) Config::get('services.public_api.rate_limit', 60);

            return [
                Limit::perMinute(max($limit, 1))->by($request->ip() ?? 'public'),
            ];
        });

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));
            $key = $email.'|'.($request->ip() ?? 'unknown');
            $limit = (int) Config::get('services.auth.login_rate_limit', 5);

            return Limit::perMinute(max($limit, 1))->by($key);
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));
            $key = 'password-reset|'.$email.'|'.($request->ip() ?? 'unknown');
            $limit = (int) Config::get('services.auth.password_reset_rate_limit', 5);

            return Limit::perMinute(max($limit, 1))->by($key);
        });
    }

    private function configureScout(): void
    {
        $scoutEnabled = (bool) config('scout.enabled', env('SCOUT_ENABLED', false));

        if (! $scoutEnabled) {
            Config::set('scout.driver', 'null');

            return;
        }

        $driver = config('scout.driver');

        if ($driver === 'meilisearch' && env('MEILISEARCH_HOST')) {
            app(EngineManager::class)->engine($driver);

            return;
        }

        Log::channel('stack')->warning('Scout enabled without Meilisearch host, falling back to database driver.');
        Config::set('scout.driver', 'database');
    }
}
