<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin';

    public function boot(): void
    {
        // The application routes are configured via bootstrap/app.php.
    }
}
