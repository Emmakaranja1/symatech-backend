<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Health check endpoint for monitoring services
     */
    public function index(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        // Database health check
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'connected';
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['database'] = 'disconnected: ' . $e->getMessage();
        }

        // Redis health check (optional)
        try {
            if (config('redis.default.host')) {
                Redis::ping();
                $checks['redis'] = 'connected';
            } else {
                $checks['redis'] = 'not configured';
            }
        } catch (\Exception $e) {
            // Don't fail health check for Redis, just log it
            $checks['redis'] = 'disconnected: ' . $e->getMessage();
        }

        // Application version
        $checks['app_version'] = config('app.version', '1.0.0');
        $checks['timestamp'] = now()->toISOString();
        $checks['environment'] = config('app.env');

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'service' => 'symatech-backend'
        ], $status === 'healthy' ? 200 : 503);
    }
}
