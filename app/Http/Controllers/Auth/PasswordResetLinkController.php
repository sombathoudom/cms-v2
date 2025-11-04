<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->string('email')->lower()->value();

        $status = Password::sendResetLink([
            'email' => $email,
        ]);

        $emailHash = hash('sha256', $email);

        $userId = User::query()
            ->where('email', $email)
            ->value('id');

        if ($status === Password::RESET_LINK_SENT) {
            AuditLogger::recordForUserId($request->user(), 'auth.password.reset-link-requested', $userId, $request);

            Log::info('auth.password.reset_link_sent', [
                'email_hash' => $emailHash,
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]);

            return back()->with('status', __($status));
        }

        Log::warning('auth.password.reset_link_failed', [
            'email_hash' => $emailHash,
            'status' => $status,
            'correlation_id' => $request->attributes->get('correlation_id'),
        ]);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
