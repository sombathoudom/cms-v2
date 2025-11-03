<?php

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    seedPermissions();
});

it('allows editors to create taxonomy entities', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $parent = Category::factory()->create(['name' => 'Parent']);
    $child = Category::create([
        'name' => 'Child',
        'slug' => 'child',
        'parent_id' => $parent->id,
    ]);

    expect($child->parent->id)->toBe($parent->id);

    $tag = Tag::create([
        'name' => 'Laravel',
        'slug' => 'laravel',
    ]);

    expect($tag->name)->toBe('Laravel');
});
