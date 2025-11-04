<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EnsureCorrelationId
{
    public function handle(Request $request, Closure $next): mixed
    {
        $correlationId = $request->headers->get('X-Correlation-ID');

        if (! $correlationId) {
            $correlationId = (string) Str::uuid();
            $request->headers->set('X-Correlation-ID', $correlationId);
        }

        $request->attributes->set('correlation_id', $correlationId);

        Log::withContext([
            'correlation_id' => $correlationId,
        ]);

        $response = $next($request);

        if ($response instanceof SymfonyResponse) {
            $response->headers->set('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
