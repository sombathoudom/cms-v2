<?php

use App\Http\Middleware\EnforceSessionTimeout;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

beforeEach(function (): void {
    seedPermissions();
});

it('rejects weak passwords during registration', function (): void {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Weak Password User',
        'email' => 'weak@example.com',
        'password' => 'weakpassword1',
        'password_confirmation' => 'weakpassword1',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
});

it('prevents password reuse on reset', function (): void {
    $user = User::factory()->create([
        'email' => 'reuser@example.com',
        'password' => Hash::make('S3curePass!789'),
    ]);

    $token = Password::createToken($user);

    $response = $this->from(route('password.reset', ['token' => $token]))->post('/reset-password', [
        'token' => $token,
        'email' => 'reuser@example.com',
        'password' => 'S3curePass!789',
        'password_confirmation' => 'S3curePass!789',
    ]);

    $response->assertSessionHasErrors('password');
});

it('accepts strong passwords on reset', function (): void {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => Hash::make('S3curePass!789'),
    ]);

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'reset@example.com',
        'password' => 'Stronger!7890',
        'password_confirmation' => 'Stronger!7890',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertTrue(Hash::check('Stronger!7890', $user->fresh()->password));
});

it('expires idle sessions and logs audit event', function (): void {
    config(['security.session.idle_timeout' => 60]);

    $user = User::factory()->create([
        'email' => 'timeout@example.com',
        'password' => Hash::make('S3curePass!789'),
    ]);

    $this->actingAs($user)
        ->withSession([EnforceSessionTimeout::SESSION_KEY => now()->subSeconds(120)->getTimestamp()]);

    $response = $this->get('/health');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', __('auth.session_timeout'));
    $this->assertGuest();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.session.timeout',
        'auditable_id' => $user->id,
    ]);
});

it('refreshes session activity when under the idle threshold', function (): void {
    config(['security.session.idle_timeout' => 300]);

    $user = User::factory()->unverified()->create([
        'email' => 'active@example.com',
        'password' => Hash::make('S3curePass!789'),
    ]);

    Route::middleware(['web', 'auth'])->get('/_test/protected', fn () => response('ok'));

    $this->actingAs($user)
        ->withSession([EnforceSessionTimeout::SESSION_KEY => now()->subSeconds(100)->getTimestamp()]);

    $response = $this->get('/_test/protected');

    $response->assertOk();
    $this->assertAuthenticatedAs($user);

    $this->assertGreaterThanOrEqual(
        now()->subSeconds(5)->getTimestamp(),
        session()->get(EnforceSessionTimeout::SESSION_KEY)
    );
});
