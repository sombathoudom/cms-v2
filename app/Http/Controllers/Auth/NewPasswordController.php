<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $resetUser = null;
        $email = $request->string('email')->lower()->value();

        $status = Password::reset(
            [
                'email' => $email,
                'password' => $request->string('password')->value(),
                'password_confirmation' => $request->string('password_confirmation')->value(),
                'token' => $request->string('token')->value(),
            ],
            function (User $user, string $password) use (&$resetUser) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $resetUser = $user;

                event(new PasswordReset($user));
            }
        );

        $emailHash = hash('sha256', $email);

        if ($status === Password::PASSWORD_RESET && $resetUser !== null) {
            AuditLogger::record($request->user(), 'auth.password.reset', $resetUser, $request);

            Log::info('auth.password.reset', [
                'user_id' => $resetUser->getKey(),
                'email_hash' => $emailHash,
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]);

            return redirect()->route('login')->with('status', __($status));
        }

        Log::warning('auth.password.reset_failed', [
            'email_hash' => $emailHash,
            'status' => $status,
            'correlation_id' => $request->attributes->get('correlation_id'),
        ]);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => [__($status)]]);
    }
}
