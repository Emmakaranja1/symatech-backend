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
            $checks['database'] = 'disconnected';
        }

        // Redis health check
        try {
            Redis::ping();
            $checks['redis'] = 'connected';
        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['redis'] = 'disconnected';
        }

        // Application version
        $checks['app_version'] = config('app.version', '1.0.0');
        $checks['timestamp'] = now()->toISOString();

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'service' => 'symatech-backend'
        ], $status === 'healthy' ? 200 : 503);
    }
}
