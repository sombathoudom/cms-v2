<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'event' => $this->event,
            'actor' => $this->user ? [
                'id' => (string) $this->user_id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'target' => [
                'type' => $this->auditable_type,
                'id' => (string) $this->auditable_id,
            ],
            'ip_address' => $this->ip_address,
            'properties' => $this->properties ?? [],
            'created_at' => optional($this->created_at)->toAtomString(),
        ];
    }
}
