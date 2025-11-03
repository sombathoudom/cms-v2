<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
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
