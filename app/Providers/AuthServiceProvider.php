<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ContentPolicy;
use App\Policies\MediaPolicy;
use App\Policies\SettingPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        Content::class => ContentPolicy::class,
        Media::class => MediaPolicy::class,
        Category::class => CategoryPolicy::class,
        Tag::class => TagPolicy::class,
        Setting::class => SettingPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability, array $arguments) {
            if (! $user->hasRole('Admin')) {
                return null;
            }

            if (
                isset($arguments[0])
                && $arguments[0] instanceof User
                && $user->is($arguments[0])
                && in_array($ability, ['delete', 'restore', 'forceDelete'], true)
            ) {
                return null;
            }

            return true;
        });
    }
}
