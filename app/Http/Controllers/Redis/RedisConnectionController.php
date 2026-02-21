<?php

namespace App\Http\Controllers\Redis;

use App\Http\Controllers\Controller;
use App\Services\Redis\RedisConnectionService;
use Illuminate\Http\JsonResponse;

class RedisConnectionController extends Controller
{
    protected $redisConnectionService;

    public function __construct(RedisConnectionService $redisConnectionService)
    {
        $this->redisConnectionService = $redisConnectionService;
    }

    public function testConnection(): JsonResponse
    {
        $result = $this->redisConnectionService->testConnection();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Redis connection is stable and operational',
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Redis connection failed',
            'data' => $result
        ], 503);
    }

    public function testBasicOperations(): JsonResponse
    {
        $result = $this->redisConnectionService->testBasicOperations();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Redis basic operations test passed',
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Redis basic operations test failed',
            'data' => $result
        ], 500);
    }

    public function testHashOperations(): JsonResponse
    {
        $result = $this->redisConnectionService->testHashOperations();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Redis hash operations test passed',
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Redis hash operations test failed',
            'data' => $result
        ], 500);
    }

    public function getRedisInfo(): JsonResponse
    {
        $result = $this->redisConnectionService->getRedisInfo();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Redis server information retrieved successfully',
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve Redis server information',
            'data' => $result
        ], 500);
    }

    public function monitorConnection(): JsonResponse
    {
        $result = $this->redisConnectionService->monitorConnection();

        if ($result['overall_status']) {
            return response()->json([
                'success' => true,
                'message' => 'All Redis tests passed - connection is stable and operational',
                'data' => $result
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Some Redis tests failed - connection may be unstable',
            'data' => $result
        ], 503);
    }

    public function healthCheck(): JsonResponse
    {
        $connectionTest = $this->redisConnectionService->testConnection();
        
        $healthStatus = [
            'status' => $connectionTest['success'] ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'response_time_ms' => $connectionTest['response_time_ms'] ?? null,
            'checks' => [
                'connection' => $connectionTest['success']
            ]
        ];

        if ($connectionTest['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Redis is healthy',
                'data' => $healthStatus
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Redis is unhealthy',
            'data' => $healthStatus
        ], 503);
    }
}
