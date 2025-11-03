<?php

use App\Models\Category;
use App\Models\Content;
use App\Models\SeoMeta;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    seedPermissions();
});

it('allows admin to create and publish content', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $category = Category::factory()->create();
    $tags = Tag::factory()->count(2)->create();
    $seoMeta = SeoMeta::factory()->create();

    $content = Content::create([
        'author_id' => $admin->id,
        'category_id' => $category->id,
        'seo_meta_id' => $seoMeta->id,
        'type' => 'post',
        'title' => 'Sample Post',
        'slug' => 'sample-post',
        'excerpt' => 'An example post',
        'body' => '<p>Body</p>',
        'status' => 'draft',
    ]);

    $content->tags()->sync($tags->pluck('id'));

    expect($content->status)->toBe('draft');

    $content->update([
        'status' => 'published',
        'published_at' => now(),
    ]);

    expect($content->refresh()->status)->toBe('published');
    expect($content->tags)->toHaveCount(2);
});

it('prevents viewers from creating content', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    $policy = app(\App\Policies\ContentPolicy::class);

    expect($policy->create($viewer))->toBeFalse();
});
