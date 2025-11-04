<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user !== null) {
            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            AuditLogger::record($user, 'auth.login', $user, $request, [
                'remember' => $request->boolean('remember'),
            ]);

            Log::info('auth.login.success', [
                'user_id' => $user->getKey(),
                'remember' => $request->boolean('remember'),
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            AuditLogger::record($user, 'auth.logout', $user, $request);

            Log::info('auth.logout', [
                'user_id' => $user->getKey(),
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
