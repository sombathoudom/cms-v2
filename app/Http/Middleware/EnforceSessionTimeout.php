<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    public const SESSION_KEY = 'security.last_activity_timestamp';

    public function handle(Request $request, Closure $next): Response
    {
        $timeout = (int) config('security.session.idle_timeout', 0);

        if ($timeout <= 0 || ! $request->hasSession()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null) {
            $lastActivity = (int) $request->session()->get(self::SESSION_KEY, 0);
            $now = now()->getTimestamp();

            if ($lastActivity > 0 && ($now - $lastActivity) > $timeout) {
                AuditLogger::record($user, 'auth.session.timeout', $user, $request, [
                    'timeout_seconds' => $timeout,
                ]);

                Log::warning('auth.session.timeout', [
                    'user_id' => $user->getKey(),
                    'timeout_seconds' => $timeout,
                    'correlation_id' => $request->attributes->get('correlation_id'),
                ]);

                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => [
                            'code' => 'SESSION_TIMEOUT',
                            'message' => __('auth.session_timeout'),
                        ],
                    ], 401);
                }

                $request->session()->flash('status', __('auth.session_timeout'));

                return redirect()->route('login');
            }
        }

        $response = $next($request);

        if ($request->user()) {
            $request->session()->put(self::SESSION_KEY, now()->getTimestamp());
        }

        return $response;
    }
}
