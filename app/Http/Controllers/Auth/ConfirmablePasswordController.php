<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! Hash::check($request->string('password')->value(), $user->password)) {
            return back()->withErrors([
                'password' => __('auth.password'),
            ], 'confirmPassword');
        }

        $request->session()->put('auth.password_confirmed_at', time());

        AuditLogger::record($user, 'auth.password.confirmed', $user, $request);

        Log::info('auth.password.confirmed', [
            'user_id' => $user->getKey(),
            'correlation_id' => $request->attributes->get('correlation_id'),
        ]);

        return redirect()->intended();
    }
}
