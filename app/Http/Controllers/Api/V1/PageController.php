<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\RespondsWithJsonError;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\ApiLog;
use App\Services\ContentDeliveryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    use RespondsWithJsonError;

    public function __construct(private readonly ContentDeliveryService $service)
    {
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $page = $this->service->getPageBySlug($slug);
        } catch (ModelNotFoundException) {
            $this->logApiCall($request, 404, ['slug' => $slug]);

            return $this->jsonError('CONTENT_NOT_FOUND', 'Page not found.', 404);
        }

        $this->service->recordView($page, $request, 'api');

        $response = (new PageResource($page))
            ->additional([
                'links' => [
                    'self' => route('api.v1.pages.show', ['slug' => $page->slug]),
                ],
            ])
            ->response();

        $this->logApiCall($request, 200, [
            'slug' => $slug,
            'content_id' => $page->id,
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

        Log::info('api.pages.request', [
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'status' => $statusCode,
            'correlation_id' => $correlationId,
            'context' => $context,
        ]);
    }
}
