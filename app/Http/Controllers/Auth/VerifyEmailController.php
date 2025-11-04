<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            AuditLogger::record($user, 'auth.email.verified', $user, $request);

            Log::info('auth.email.verified', [
                'user_id' => $user->getKey(),
                'email_hash' => hash('sha256', Str::lower($user->email)),
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]);
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }
}
