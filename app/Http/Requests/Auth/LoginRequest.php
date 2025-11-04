<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');
        $remember = $this->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($this->throttleKey());

            $email = Str::lower($this->string('email')->value());
            $emailHash = hash('sha256', $email);
            $userId = User::query()->where('email', $email)->value('id');

            AuditLogger::recordForUserId(null, 'auth.login.failed', $userId, $this, [
                'reason' => 'invalid_credentials',
            ]);

            Log::warning('auth.login.failed', [
                'email_hash' => $emailHash,
                'ip' => $this->ip(),
                'correlation_id' => $this->attributes->get('correlation_id'),
            ]);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        $limit = (int) config('services.auth.login_rate_limit', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $limit)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ])->status(429);
    }

    public function throttleKey(): string
    {
        return Str::lower($this->string('email')->value()).'|'.$this->ip();
    }
}
