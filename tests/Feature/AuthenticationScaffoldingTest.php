<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function (): void {
    seedPermissions();
    Notification::fake();
});

it('E1-F1-I1 registers a viewer and dispatches verification', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'new-user@example.com',
        'password' => 'S3curePass!789',
        'password_confirmation' => 'S3curePass!789',
    ]);

    $response->assertRedirect('/admin');

    $user = User::where('email', 'new-user@example.com')->firstOrFail();

    expect($user->hasRole('Viewer'))->toBeTrue();
    expect($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.register',
        'auditable_type' => User::class,
        'auditable_id' => $user->getKey(),
    ]);
});

it('E1-F1-I1 logs in with remember me and records audit trail', function (): void {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('S3curePass!789'),
    ]);

    $response = $this->post('/login', [
        'email' => 'login@example.com',
        'password' => 'S3curePass!789',
        'remember' => true,
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->last_login_at)->not()->toBeNull();

    $rememberCookie = 'remember_web_'.sha1(SessionGuard::class);
    $this->assertTrue(
        collect($response->headers->getCookies())->contains(fn ($cookie) => $cookie->getName() === $rememberCookie),
        'Expected remember me cookie to be present.'
    );

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.login',
        'auditable_id' => $user->id,
        'auditable_type' => User::class,
    ]);
});

it('E1-F1-I1 throttles repeated login attempts', function (): void {
    $email = 'throttle@example.com';
    User::factory()->create([
        'email' => $email,
        'password' => Hash::make('S3curePass!789'),
    ]);

    $key = strtolower($email).'|127.0.0.1';
    RateLimiter::clear($key);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => $email,
            'password' => 'invalid-password',
        ])->assertSessionHasErrors('email');
    }

    $this->postJson('/login', [
        'email' => $email,
        'password' => 'invalid-password',
    ])->assertStatus(429);

    expect(RateLimiter::tooManyAttempts($key, (int) config('services.auth.login_rate_limit', 5)))->toBeTrue();
});

it('E1-F1-I1 emails password reset link and logs request', function (): void {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => Hash::make('S3curePass!789'),
    ]);

    $response = $this->post('/forgot-password', [
        'email' => 'reset@example.com',
    ]);

    $response->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.password.reset-link-requested',
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
    ]);
});

it('E1-F1-I1 resets password successfully', function (): void {
    $user = User::factory()->create([
        'email' => 'update@example.com',
        'password' => Hash::make('S3curePass!789'),
    ]);

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'update@example.com',
        'password' => 'NewPassword!456',
        'password_confirmation' => 'NewPassword!456',
    ]);

    $response->assertRedirect(route('login'));

    $this->assertTrue(Hash::check('NewPassword!456', $user->fresh()->password));

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.password.reset',
        'auditable_id' => $user->id,
    ]);
});

it('E1-F1-I1 verifies email addresses via signed URL', function (): void {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect('/admin?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'auth.email.verified',
        'auditable_id' => $user->id,
    ]);
});
