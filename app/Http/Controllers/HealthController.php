<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            return response()->json([
                'error' => [
                    'code' => 'DB_UNAVAILABLE',
                    'message' => 'Database connection unavailable.',
                ],
            ], 503);
        }

        try {
            Redis::connection()->ping();
        } catch (\Throwable $exception) {
            return response()->json([
                'error' => [
                    'code' => 'REDIS_UNAVAILABLE',
                    'message' => 'Redis connection unavailable.',
                ],
            ], 503);
        }

        $start = defined('LARAVEL_START') ? LARAVEL_START : ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));

        return response()->json([
            'status' => 'ok',
            'uptime' => number_format(max(0, microtime(true) - $start), 2),
            'db' => 'ok',
            'redis' => 'ok',
        ]);
    }
}
