<?php

use App\Models\ApiLog;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Content;
use App\Models\Tag;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function (): void {
    Cache::flush();
    RateLimiter::clear('public-content-api|127.0.0.1');
    RateLimiter::clear('public-content-api|::1');
});

it('E8-F1-I1 returns JSON:API post collection with filters', function (): void {
    $category = Category::factory()->create([
        'name' => 'News',
        'slug' => 'news',
    ]);
    $tag = Tag::factory()->create([
        'name' => 'Release',
        'slug' => 'release',
    ]);

    $matching = Content::factory()
        ->published()
        ->post()
        ->create([
            'category_id' => $category->id,
            'title' => 'API Filter Match',
            'slug' => 'api-filter-match',
            'published_at' => now()->subDay(),
        ]);

    $matching->tags()->attach($tag->id);

    Content::factory()->published()->post()->create([
        'title' => 'Irrelevant Post',
        'slug' => 'irrelevant-post',
    ]);

    $response = $this->getJson('/api/v1/posts?category=news&tag=release&per_page=10');

    $response->assertOk();
    $response->assertHeader('X-Correlation-ID');

    $response->assertJson(fn (AssertableJson $json) => $json
        ->where('data.0.type', 'posts')
        ->where('data.0.attributes.title', 'API Filter Match')
        ->where('data.0.relationships.category.meta.slug', 'news')
        ->where('data.0.relationships.tags.meta.0.slug', 'release')
        ->where('meta.total', 1)
        ->where('meta.per_page', 10)
        ->where('links.self', function (string $value) {
            $path = parse_url($value, PHP_URL_PATH);
            parse_str((string) parse_url($value, PHP_URL_QUERY), $query);

            return $path === '/api/v1/posts'
                && $query === ['category' => 'news', 'per_page' => '10', 'tag' => 'release'];
        })
        ->etc());

    $this->assertDatabaseHas('api_logs', [
        'endpoint' => 'api/v1/posts',
        'response_code' => 200,
    ]);
});

it('E8-F1-I1 returns JSON:API post detail and records audit log', function (): void {
    $post = Content::factory()->published()->post()->create([
        'title' => 'API Detail',
        'slug' => 'api-detail',
    ]);

    $response = $this->getJson('/api/v1/posts/'.$post->slug);

    $response->assertOk();
    $response->assertJson(fn (AssertableJson $json) => $json
        ->where('data.type', 'posts')
        ->where('data.id', (string) $post->id)
        ->where('data.attributes.title', 'API Detail')
        ->etc());

    $this->assertDatabaseHas('audit_logs', [
        'auditable_id' => $post->id,
        'event' => 'content.viewed',
    ]);

    $this->assertDatabaseHas('api_logs', [
        'endpoint' => 'api/v1/posts/'.$post->slug,
        'response_code' => 200,
    ]);
});

it('E8-F1-I1 returns error schema when post missing', function (): void {
    $response = $this->getJson('/api/v1/posts/missing-slug');

    $response->assertNotFound();
    $response->assertExactJson([
        'error' => [
            'code' => 'CONTENT_NOT_FOUND',
            'message' => 'Post not found.',
        ],
    ]);

    $this->assertDatabaseHas('api_logs', [
        'endpoint' => 'api/v1/posts/missing-slug',
        'response_code' => 404,
    ]);
});

it('E8-F1-I1 enforces rate limiting for public API', function (): void {
    RateLimiter::for('public-content-api', function (Request $request) {
        return [Limit::perMinute(1)->by($request->ip() ?? 'testing')];
    });

    $post = Content::factory()->published()->post()->create([
        'slug' => 'rate-limited-post',
    ]);

    $this->getJson('/api/v1/posts/'.$post->slug)->assertOk();

    $second = $this->getJson('/api/v1/posts/'.$post->slug);
    $second->assertStatus(429);
    $second->assertExactJson([
        'error' => [
            'code' => 'RATE_LIMIT_EXCEEDED',
            'message' => 'Too many requests.',
        ],
    ]);

    RateLimiter::for('public-content-api', function (Request $request) {
        return [Limit::perMinute((int) config('services.public_api.rate_limit', 60))->by($request->ip() ?? 'public')];
    });
    RateLimiter::clear('public-content-api|127.0.0.1');
});

it('E8-F1-I1 returns JSON:API page detail', function (): void {
    $page = Content::factory()->published()->page()->create([
        'slug' => 'api-page',
        'title' => 'API Page',
        'body' => 'API page body',
    ]);

    $response = $this->getJson('/api/v1/pages/'.$page->slug);

    $response->assertOk();
    $response->assertJson(fn (AssertableJson $json) => $json
        ->where('data.type', 'pages')
        ->where('data.id', (string) $page->id)
        ->where('data.attributes.title', 'API Page')
        ->etc());
});
