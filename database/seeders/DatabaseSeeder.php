<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // DO NOT USE IN PRODUCTION: demo data only.

        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'content.view',
            'content.create',
            'content.update',
            'content.delete',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
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

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('S3curePass!789'),
        ]);
        $admin->assignRole('Admin');

        $editor = User::factory()->create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('S3curePass!789'),
        ]);
        $editor->assignRole('Editor');

        $author = User::factory()->create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => Hash::make('S3curePass!789'),
        ]);
        $author->assignRole('Author');

        $viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('S3curePass!789'),
        ]);
        $viewer->assignRole('Viewer');

        $categories = Category::factory()->count(3)->create();
        $tags = Tag::factory()->count(5)->create();
        $settings = [
            ['key' => 'site.name', 'value' => ['value' => 'Demo CMS']],
            ['key' => 'site.tagline', 'value' => ['value' => 'Manageable content for everyone']],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }

        $media = Media::factory()->count(5)->create();

        Content::factory()->count(6)->published()->post()->create()->each(function (Content $content) use ($tags, $media, $categories, $editor) {
        Content::factory()->count(5)->create()->each(function (Content $content) use ($tags, $media, $categories, $editor) {
            $content->tags()->sync($tags->random(2));
            $content->category()->associate($categories->random());
            $content->featuredMedia()->associate($media->random());
            $content->author()->associate($editor);
            $content->save();
        });

        Content::factory()->count(2)->published()->page()->create()->each(function (Content $page) use ($media, $editor) {
            $page->featuredMedia()->associate($media->random());
            $page->author()->associate($editor);
            $page->save();
        });
    }
}
