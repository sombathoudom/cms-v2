<?php

namespace App\Services;

use App\DTOs\ContentFilterData;
use App\Models\AuditLog;
use App\Models\Content;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ContentDeliveryService
{
    private const CACHE_TTL_SECONDS = 300;

    public function listPosts(ContentFilterData $filters, int $perPage = 10): LengthAwarePaginator
    {
        if ($filters->search && $this->shouldUseScout()) {
            return Content::search($filters->search)
                ->query(function (Builder $query) use ($filters) {
                    $this->applyPostConstraints($query, $filters);
                })
                ->paginate($perPage);
        }

        $query = Content::query();
        $this->applyPostConstraints($query, $filters);

        return $query->paginate($perPage);
    }

    public function getPostBySlug(string $slug): Content
    {
        return $this->rememberContent("post:{$slug}", function () use ($slug) {
            return Content::query()
                ->published()
                ->posts()
                ->with($this->publicRelations())
                ->where('slug', $slug)
                ->firstOrFail();
        });
    }

    public function getPageBySlug(string $slug): Content
    {
        return $this->rememberContent("page:{$slug}", function () use ($slug) {
            return Content::query()
                ->published()
                ->pages()
                ->with($this->publicRelations())
                ->where('slug', $slug)
                ->firstOrFail();
        });
    }

    public function getArchiveSummary(): Collection
    {
        $driver = DB::connection()->getDriverName();
        $yearExpression = $driver === 'sqlite'
            ? "CAST(strftime('%Y', published_at) AS INTEGER)"
            : 'YEAR(published_at)';
        $monthExpression = $driver === 'sqlite'
            ? "CAST(strftime('%m', published_at) AS INTEGER)"
            : 'MONTH(published_at)';

        return DB::table('contents')
            ->selectRaw("{$yearExpression} as year, {$monthExpression} as month, COUNT(*) as total")
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('type', 'post')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(static fn (object $row) => [
                'year' => (int) $row->year,
                'month' => (int) $row->month,
                'total' => (int) $row->total,
            ]);
    }

    public function recordView(Content $content, Request $request, string $channel): void
    {
        $correlationId = $request->attributes->get('correlation_id');

        Log::info('content.viewed', [
            'content_id' => $content->id,
            'content_slug' => $content->slug,
            'channel' => $channel,
            'correlation_id' => $correlationId,
        ]);

        AuditLog::create([
            'user_id' => optional($request->user())->id,
            'auditable_type' => Content::class,
            'auditable_id' => $content->id,
            'event' => 'content.viewed',
            'properties' => [
                'channel' => $channel,
                'correlation_id' => $correlationId,
                'path' => $request->path(),
            ],
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * @param  Builder<Content>  $query
     */
    private function applyPostConstraints(Builder $query, ContentFilterData $filters): void
    {
        $query
            ->published()
            ->posts()
            ->with($this->publicRelations())
            ->orderByDesc('is_sticky')
            ->orderByDesc('published_at');

        if ($filters->category) {
            $query->whereHas('category', function (Builder $categoryQuery) use ($filters) {
                $categoryQuery->where('slug', $filters->category);
            });
        }

        if ($filters->tag) {
            $query->whereHas('tags', function (Builder $tagQuery) use ($filters) {
                $tagQuery->where('slug', $filters->tag);
            });
        }

        if ($filters->year) {
            $query->whereYear('published_at', $filters->year);
        }

        if ($filters->month) {
            $query->whereMonth('published_at', $filters->month);
        }

        if ($filters->search && ! $this->shouldUseScout()) {
            $searchTerm = '%' . str_replace('%', '\\%', $filters->search) . '%';
            $query->where(function (Builder $searchQuery) use ($searchTerm) {
                $searchQuery
                    ->where('title', 'like', $searchTerm)
                    ->orWhere('excerpt', 'like', $searchTerm)
                    ->orWhere('body', 'like', $searchTerm);
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function publicRelations(): array
    {
        return ['author', 'category', 'tags', 'featuredMedia'];
    }

    private function rememberContent(string $key, callable $resolver): Content
    {
        $cacheKey = Str::replace(['/', '::'], '_', $key);

        return Cache::remember("public_content_{$cacheKey}", self::CACHE_TTL_SECONDS, static function () use ($resolver) {
            return $resolver();
        });
    }

    private function shouldUseScout(): bool
    {
        return Config::get('scout.driver') !== 'null';
    }
}
