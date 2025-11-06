<?php

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;

it('E1-F2-I1 lists users with filters and search', function (): void {
    seedPermissions();

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $target = User::factory()->create([
        'name' => 'Filter Target',
        'email' => 'filter@example.com',
        'status' => UserStatus::INACTIVE->value,
    ]);
    $target->assignRole('Editor');

    User::factory()->count(2)->create();

    $response = $this->actingAs($admin)->getJson(route('api.v1.users.index', [
        'search' => 'Filter Target',
        'role' => 'Editor',
        'status' => UserStatus::INACTIVE->value,
    ]));

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonFragment([
            'email' => 'filter@example.com',
            'status' => UserStatus::INACTIVE->value,
        ]);
});

it('E1-F2-I1 blocks user list access without permission', function (): void {
    seedPermissions();

    $viewer = User::factory()->create();
    $viewer->assignRole('Viewer');

    $this->actingAs($viewer)
        ->getJson(route('api.v1.users.index'))
        ->assertForbidden();
});

it('E1-F2-I1 creates users via API and records audit entries', function (): void {
    seedPermissions();

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $response = $this->actingAs($admin)->postJson(route('api.v1.users.store'), [
        'name' => 'New Admin User',
        'email' => 'new-user@example.com',
        'password' => 'Str0ngPass!234',
        'password_confirmation' => 'Str0ngPass!234',
        'status' => UserStatus::ACTIVE->value,
        'roles' => ['Editor'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'new-user@example.com');

    $created = User::where('email', 'new-user@example.com')->firstOrFail();

    expect($created->hasRole('Editor'))->toBeTrue();
    expect(AuditLog::where('event', 'user.created')->where('auditable_id', $created->id)->exists())->toBeTrue();
});

it('E1-F2-I1 updates, deletes, and restores users with audit logs', function (): void {
    seedPermissions();

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $user = User::factory()->create([
        'status' => UserStatus::INACTIVE->value,
    ]);
    $user->assignRole('Viewer');

    $this->actingAs($admin)->putJson(route('api.v1.users.update', $user), [
        'name' => 'Updated User',
        'status' => UserStatus::ACTIVE->value,
        'roles' => ['Author'],
    ])->assertOk()
        ->assertJsonPath('data.name', 'Updated User');

    $user->refresh();

    expect($user->status)->toBe(UserStatus::ACTIVE);
    expect($user->hasRole('Author'))->toBeTrue();

    $this->actingAs($admin)
        ->deleteJson(route('api.v1.users.destroy', $user))
        ->assertNoContent();

    $user->refresh();
    expect($user->trashed())->toBeTrue();

    $this->actingAs($admin)
        ->postJson(route('api.v1.users.restore', $user->id))
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null);

    $user->refresh();
    expect($user->trashed())->toBeFalse();

    expect(AuditLog::where('event', 'user.updated')->where('auditable_id', $user->id)->exists())->toBeTrue();
    expect(AuditLog::where('event', 'user.deleted')->where('auditable_id', $user->id)->exists())->toBeTrue();
    expect(AuditLog::where('event', 'user.restored')->where('auditable_id', $user->id)->exists())->toBeTrue();
});
