<?php

namespace App\Http\Controllers\Redis;

use App\Http\Controllers\Controller;
use App\Services\Redis\RateLimitService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RateLimitController extends Controller
{
    protected $rateLimitService;

    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    public function checkRateLimit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
            'max_attempts' => 'required|integer|min:1|max:1000',
            'decay_seconds' => 'required|integer|min:1|max:86400',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('identifier');
        $maxAttempts = $request->input('max_attempts');
        $decaySeconds = $request->input('decay_seconds');

        $result = $this->rateLimitService->checkRateLimit($identifier, $maxAttempts, $decaySeconds);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function clearRateLimit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('identifier');
        $success = $this->rateLimitService->clearRateLimit($identifier);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Rate limit cleared successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to clear rate limit or no rate limit found'
        ], 404);
    }

    public function getRateLimitStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('identifier');
        $status = $this->rateLimitService->getRateLimitStatus($identifier);

        if ($status) {
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No rate limit found for this identifier'
        ], 404);
    }

    public function incrementRateLimit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('identifier');
        $attempts = $this->rateLimitService->incrementRateLimit($identifier);

        return response()->json([
            'success' => true,
            'data' => [
                'identifier' => $identifier,
                'attempts' => $attempts
            ]
        ]);
    }

    public function checkMultipleRateLimits(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limits' => 'required|array',
            'limits.*.name' => 'required|string|max:255',
            'limits.*.identifier' => 'required|string|max:255',
            'limits.*.max_attempts' => 'required|integer|min:1|max:1000',
            'limits.*.decay_seconds' => 'required|integer|min:1|max:86400',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $limits = $request->input('limits');
        $results = $this->rateLimitService->checkMultipleRateLimits($limits);

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    public function isBlocked(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
            'max_attempts' => 'required|integer|min:1|max:1000',
            'decay_seconds' => 'required|integer|min:1|max:86400',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->input('identifier');
        $maxAttempts = $request->input('max_attempts');
        $decaySeconds = $request->input('decay_seconds');

        $isBlocked = $this->rateLimitService->isBlocked($identifier, $maxAttempts, $decaySeconds);

        return response()->json([
            'success' => true,
            'data' => [
                'identifier' => $identifier,
                'is_blocked' => $isBlocked,
                'max_attempts' => $maxAttempts,
                'decay_seconds' => $decaySeconds
            ]
        ]);
    }

    public function testRateLimiting(): JsonResponse
    {
        $testIdentifier = 'test_' . time() . '_' . rand(1000, 9999);
        $maxAttempts = 5;
        $decaySeconds = 60;

        $results = [];
        
        for ($i = 1; $i <= $maxAttempts + 2; $i++) {
            $result = $this->rateLimitService->checkRateLimit($testIdentifier, $maxAttempts, $decaySeconds);
            $results[] = [
                'attempt' => $i,
                'allowed' => $result['allowed'],
                'attempts' => $result['attempts'],
                'remaining' => $result['remaining']
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Rate limiting test completed',
            'data' => [
                'test_identifier' => $testIdentifier,
                'max_attempts' => $maxAttempts,
                'decay_seconds' => $decaySeconds,
                'results' => $results
            ]
        ]);
    }
}
