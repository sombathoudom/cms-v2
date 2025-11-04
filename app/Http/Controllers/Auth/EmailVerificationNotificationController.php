<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended('/')->with('status', __('Email address already verified.'));
        }

        $user->sendEmailVerificationNotification();

        AuditLogger::record($user, 'auth.email.verification-notification-sent', $user, $request);

        Log::info('auth.email.verification_notification_sent', [
            'user_id' => $user->getKey(),
            'email_hash' => hash('sha256', Str::lower($user->email)),
            'correlation_id' => $request->attributes->get('correlation_id'),
        ]);

        return back()->with('status', __('A new verification link has been sent to your email address.'));
    }
}
