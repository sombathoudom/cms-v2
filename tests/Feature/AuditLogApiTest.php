<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;

it('E1-F1-I3 returns filtered audit logs for authorized users', function (): void {
    seedPermissions();

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $target = User::factory()->create();

    $now = Carbon::now();

    AuditLog::factory()
        ->for($admin, 'user')
        ->for($target, 'auditable')
        ->create([
            'event' => 'auth.login',
            'created_at' => $now->copy()->subDays(2),
        ]);

    $matching = AuditLog::factory()
        ->for($admin, 'user')
        ->for($target, 'auditable')
        ->create([
            'event' => 'auth.logout',
            'created_at' => $now->copy()->subHours(3),
        ]);

    AuditLog::factory()->create();

    $response = $this->actingAs($admin)->getJson(route('api.v1.audit-logs.index', [
        'user_id' => $admin->id,
        'event' => 'auth.logout',
        'date_from' => $now->copy()->subDay()->toDateString(),
        'date_to' => $now->toDateString(),
        'per_page' => 50,
    ]));

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonFragment([
            'event' => 'auth.logout',
            'ip_address' => $matching->ip_address,
        ]);
});

it('E1-F1-I3 blocks audit log access without permission', function (): void {
    seedPermissions();

    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    $this->actingAs($viewer)
        ->getJson(route('api.v1.audit-logs.index'))
        ->assertForbidden();
});

it('E1-F1-I3 audit entries cannot be mutated', function (): void {
    seedPermissions();

    $log = AuditLog::factory()->create();

    expect(fn () => $log->update(['event' => 'changed']))->toThrow(\LogicException::class);
    expect(fn () => $log->delete())->toThrow(\LogicException::class);
});
