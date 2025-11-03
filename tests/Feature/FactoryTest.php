<?php

use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;

it('generates valid factories for core models', function () {
    expect(User::factory()->create())->toBeInstanceOf(User::class);
    expect(Category::factory()->create())->toBeInstanceOf(Category::class);
    expect(Tag::factory()->create())->toBeInstanceOf(Tag::class);
    expect(Media::factory()->create())->toBeInstanceOf(Media::class);

    $content = Content::factory()->create();
    expect($content->revisions()->create([
        'author_id' => $content->author_id,
        'revision_number' => 1,
    ]))->toBeTruthy();

    expect(Setting::factory()->create())->toBeInstanceOf(Setting::class);
});
