<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\RespondsWithJsonError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AuditLogIndexRequest;
use App\Http\Resources\AuditLogResource as AuditLogResourceData;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    use RespondsWithJsonError;

    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);
        $perPage = min(max($perPage, 1), 100);

        $query = AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at');

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['event'])) {
            $query->where('event', $validated['event']);
        }

        if (isset($validated['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['date_from'])->startOfDay());
        }

        if (isset($validated['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['date_to'])->endOfDay());
        }

        $logs = $query->paginate($perPage);
        $logs->appends($request->query());

        return response()->json([
            'data' => AuditLogResourceData::collection($logs->items())->resolve(),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
            'links' => [
                'self' => $request->fullUrl(),
                'first' => $logs->url(1),
                'last' => $logs->url($logs->lastPage()),
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
        ]);
    }
}
