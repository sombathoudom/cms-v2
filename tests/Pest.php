<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

beforeEach(function (): void {
    config()->set('security.password.uncompromised', false);
});

function seedPermissions(): void
{
    $permissions = [
        'content.view',
        'content.create',
        'content.update',
        'content.delete',
        'media.view',
        'media.create',
        'media.update',
        'media.delete',
        'taxonomy.view',
        'taxonomy.create',
        'taxonomy.update',
        'taxonomy.delete',
        'settings.view',
        'settings.create',
        'settings.update',
        'settings.delete',
        'audit.view',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $roles = [
        'Admin' => $permissions,
        'Editor' => [
            'content.view', 'content.create', 'content.update',
            'media.view', 'media.create', 'media.update',
            'taxonomy.view', 'taxonomy.create', 'taxonomy.update',
            'settings.view',
        ],
        'Author' => [
            'content.view', 'content.create', 'content.update',
            'media.view', 'media.create',
            'taxonomy.view',
        ],
        'Viewer' => [
            'content.view',
            'media.view',
            'taxonomy.view',
        ],
    ];

    foreach ($roles as $roleName => $rolePermissions) {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($rolePermissions);
    }

    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
}
