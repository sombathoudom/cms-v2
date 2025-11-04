<?php

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    seedPermissions();
});

it('restricts settings management to admins', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $setting = Setting::create([
        'key' => 'site.title',
        'value' => ['value' => 'CMS'],
    ]);

    expect($setting->value['value'])->toBe('CMS');

    $setting->update(['value' => ['value' => 'Updated']]);
    expect($setting->refresh()->value['value'])->toBe('Updated');
});

it('prevents authors from deleting settings', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    $policy = app(\App\Policies\SettingPolicy::class);

    expect($policy->delete($author, new Setting()))->toBeFalse();
});
