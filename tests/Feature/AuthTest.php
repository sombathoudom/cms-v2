<?php

use App\Models\Content;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    seedPermissions();
});

it('grants admin gate bypass', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $content = Content::factory()->create();

    expect(Gate::forUser($admin)->check('update', $content))->toBeTrue();
});

it('denies viewer update ability', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    $content = Content::factory()->create();

    expect(Gate::forUser($viewer)->check('update', $content))->toBeFalse();
});
