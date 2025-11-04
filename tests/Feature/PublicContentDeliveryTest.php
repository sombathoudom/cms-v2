<?php

use App\Models\AuditLog;
use App\Models\Content;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('renders published posts on index', function () {
    $post = Content::factory()->published()->post()->create([
        'title' => 'Public Hello',
        'slug' => 'public-hello',
    ]);

    $response = $this->get('/posts');

    $response->assertOk();
    $response->assertSee($post->title);
    $response->assertHeader('X-Correlation-ID');
});

it('filters posts by search term', function () {
    Content::factory()->published()->post()->create([
        'title' => 'Laravel Searchable',
        'slug' => 'laravel-searchable',
    ]);

    Content::factory()->published()->post()->create([
        'title' => 'Hidden Entry',
        'slug' => 'hidden-entry',
    ]);

    $response = $this->get('/posts?q=Laravel');

    $response->assertOk();
    $response->assertSee('Laravel Searchable');
    $response->assertDontSee('Hidden Entry');
});

it('supports archive filtering', function () {
    $targetDate = Carbon::create(2023, 5, 1);
    Content::factory()->published()->post()->create([
        'title' => 'Archive Eligible',
        'slug' => 'archive-eligible',
        'publish_at' => $targetDate,
        'published_at' => $targetDate,
    ]);

    Content::factory()->published()->post()->create([
        'title' => 'Outside Range',
        'slug' => 'outside-range',
        'publish_at' => Carbon::create(2022, 1, 1),
        'published_at' => Carbon::create(2022, 1, 1),
    ]);

    $response = $this->get('/posts/archive/2023/5');

    $response->assertOk();
    $response->assertSee('Archive Eligible');
    $response->assertDontSee('Outside Range');
});

it('returns 404 for unpublished posts', function () {
    $draft = Content::factory()->draft()->post()->create([
        'title' => 'Draft Post',
        'slug' => 'draft-post',
    ]);

    $this->get('/posts/'.$draft->slug)->assertNotFound();
});

it('renders published page content', function () {
    $page = Content::factory()->published()->page()->create([
        'title' => 'About Us',
        'slug' => 'about-us',
        'body' => 'Company information',
    ]);

    $response = $this->get('/pages/'.$page->slug);

    $response->assertOk();
    $response->assertSee('Company information');
});

it('records an audit log entry for post views', function () {
    $post = Content::factory()->published()->post()->create([
        'slug' => 'audit-log-post',
    ]);

    $this->get('/posts/'.$post->slug)->assertOk();

    expect(AuditLog::query()->where('auditable_id', $post->id)->where('event', 'content.viewed')->exists())->toBeTrue();
});
