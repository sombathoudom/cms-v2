<?php

use App\Models\Content;
use App\Models\User;

beforeEach(function () {
    seedPermissions();
});

it('allows authors to update their own drafts', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    $content = Content::factory()->create(['author_id' => $author->id, 'status' => 'draft']);

    $policy = app(\App\Policies\ContentPolicy::class);

    expect($policy->update($author, $content))->toBeTrue();
});

it('blocks viewers from deleting content', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    $content = Content::factory()->create();

    $policy = app(\App\Policies\ContentPolicy::class);

    expect($policy->delete($viewer, $content))->toBeFalse();
});
