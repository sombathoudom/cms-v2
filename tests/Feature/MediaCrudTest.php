<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    seedPermissions();
});

it('allows editors to manage media entries', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $media = Media::create([
        'uuid' => (string) Str::uuid(),
        'disk' => 'public',
        'filename' => 'demo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 2048,
        'uploaded_by' => $editor->id,
    ]);

    expect($media->uploader->id)->toBe($editor->id);

    $media->update(['alt_text' => 'Updated']);

    expect($media->refresh()->alt_text)->toBe('Updated');
});
