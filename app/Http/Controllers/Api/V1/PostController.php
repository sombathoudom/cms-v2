<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ContentFilterData;
use App\Http\Controllers\Concerns\RespondsWithJsonError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PostIndexRequest;
use App\Http\Resources\PostResource;
use App\Models\ApiLog;
use App\Services\ContentDeliveryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    use RespondsWithJsonError;

    public function __construct(private readonly ContentDeliveryService $service)
    {
    }

    public function index(PostIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $filters = ContentFilterData::fromArray($validated);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $posts = $this->service->listPosts($filters, $perPage);
        $posts->appends($request->query());

        $resource = PostResource::collection(collect($posts->items()))->resolve();

        $response = response()->json([
            'data' => $resource,
            'meta' => [
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ],
            'links' => [
                'self' => $request->fullUrl(),
                'first' => $posts->url(1),
                'last' => $posts->url($posts->lastPage()),
                'prev' => $posts->previousPageUrl(),
                'next' => $posts->nextPageUrl(),
            ],
        ]);

        $this->logApiCall($request, 200, [
            'filters' => $validated,
            'returned' => count($posts->items()),
        ]);

        return $response;
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $post = $this->service->getPostBySlug($slug);
        } catch (ModelNotFoundException) {
            $this->logApiCall($request, 404, ['slug' => $slug]);

            return $this->jsonError('CONTENT_NOT_FOUND', 'Post not found.', 404);
        }

        $this->service->recordView($post, $request, 'api');

        $response = (new PostResource($post))
            ->additional([
                'links' => [
                    'self' => route('api.v1.posts.show', ['slug' => $post->slug]),
                ],
            ])
            ->response();

        $this->logApiCall($request, 200, [
            'slug' => $slug,
            'content_id' => $post->id,
        ]);

        return $response;
    }

    private function logApiCall(Request $request, int $statusCode, array $context): void
    {
        $correlationId = $request->attributes->get('correlation_id');

        ApiLog::create([
            'method' => $request->getMethod(),
            'endpoint' => $request->path(),
            'response_code' => $statusCode,
            'payload' => [
                'context' => $context,
                'query' => $request->query(),
                'correlation_id' => $correlationId,
            ],
        ]);

        Log::info('api.posts.request', [
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'status' => $statusCode,
            'correlation_id' => $correlationId,
            'context' => $context,
        ]);
    }
}
