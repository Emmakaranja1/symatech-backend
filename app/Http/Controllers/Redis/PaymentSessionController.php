<?php

namespace App\Http\Controllers\Redis;

use App\Http\Controllers\Controller;
use App\Services\Redis\PaymentSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentSessionController extends Controller
{
    protected $paymentSessionService;

    public function __construct(PaymentSessionService $paymentSessionService)
    {
        $this->paymentSessionService = $paymentSessionService;
    }

    public function createSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'user_id' => 'required|integer',
            'payment_method' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sessionId = Str::uuid()->toString();
        $paymentData = $request->only(['amount', 'currency', 'user_id', 'payment_method', 'description', 'metadata']);
        $paymentData['status'] = 'pending';
        $paymentData['session_id'] = $sessionId;

        $success = $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Payment session created successfully',
                'data' => [
                    'session_id' => $sessionId,
                    'payment_data' => $paymentData
                ]
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to create payment session'
        ], 500);
    }

    public function getSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sessionId = $request->input('session_id');
        $sessionData = $this->paymentSessionService->getPaymentSession($sessionId);

        if ($sessionData) {
            return response()->json([
                'success' => true,
                'data' => $sessionData
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment session not found or expired'
        ], 404);
    }

    public function updateSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
            'status' => 'nullable|string|in:pending,processing,completed,failed,cancelled',
            'transaction_id' => 'nullable|string|max:255',
            'payment_response' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sessionId = $request->input('session_id');
        $updates = array_filter($request->only(['status', 'transaction_id', 'payment_response', 'metadata']));

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid updates provided'
            ], 422);
        }

        $success = $this->paymentSessionService->updatePaymentSession($sessionId, $updates);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Payment session updated successfully',
                'data' => [
                    'session_id' => $sessionId,
                    'updates' => $updates
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update payment session or session not found'
        ], 404);
    }

    public function deleteSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sessionId = $request->input('session_id');
        $success = $this->paymentSessionService->deletePaymentSession($sessionId);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Payment session deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete payment session or session not found'
        ], 404);
    }

    public function extendSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
            'seconds' => 'nullable|integer|min:60|max:3600',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sessionId = $request->input('session_id');
        $seconds = $request->input('seconds', 300);

        $success = $this->paymentSessionService->extendPaymentSession($sessionId, $seconds);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Payment session extended successfully',
                'data' => [
                    'session_id' => $sessionId,
                    'extended_by_seconds' => $seconds
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to extend payment session or session not found'
        ], 404);
    }

    public function checkSessionValidity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sessionId = $request->input('session_id');
        $isValid = $this->paymentSessionService->isPaymentSessionValid($sessionId);
        $ttl = $this->paymentSessionService->getPaymentSessionTTL($sessionId);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
                'is_valid' => $isValid,
                'ttl_seconds' => $ttl,
                'expires_at' => $ttl > 0 ? now()->addSeconds($ttl)->toISOString() : null
            ]
        ]);
    }
}
