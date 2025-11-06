<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * @param array<string, mixed> $properties
     */
    public static function record(?User $actor, string $event, ?Model $auditable, Request $request, array $properties = []): void
    {
        if ($auditable === null || ! $auditable->getKey()) {
            return;
        }

        $correlationId = $request->attributes->get('correlation_id');

        $payload = array_merge($properties, [
            'correlation_id' => $correlationId,
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        AuditLog::create([
            'user_id' => $actor?->getKey(),
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'properties' => $payload,
            'ip_address' => $request->ip(),
        ]);

        Log::channel('audit')->info('audit.event', [
            'event' => $event,
            'user_id' => $actor?->getKey(),
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'correlation_id' => $correlationId,
            'ip' => $request->ip(),
            'properties' => $payload,
            'recorded_at' => now()->toAtomString(),
        ]);
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function recordForUserId(?User $actor, string $event, ?int $userId, Request $request, array $properties = []): void
    {
        if (! $userId) {
            return;
        }

        $auditable = User::find($userId);

        if ($auditable === null) {
            return;
        }

        self::record($actor, $event, $auditable, $request, $properties);
    }
}
