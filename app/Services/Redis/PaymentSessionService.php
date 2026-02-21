<?php

namespace App\Services\Redis;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class PaymentSessionService
{
    private const PAYMENT_PREFIX = 'payment:';
    private const PAYMENT_EXPIRY = 1800; // 30 minutes

    public function createPaymentSession(string $sessionId, array $paymentData): bool
    {
        try {
            $paymentKey = self::PAYMENT_PREFIX . $sessionId;
            $paymentData['created_at'] = now()->toISOString();
            $paymentData['expires_at'] = now()->addSeconds(self::PAYMENT_EXPIRY)->toISOString();

            Redis::setex($paymentKey, self::PAYMENT_EXPIRY, json_encode($paymentData));

            Log::info("Payment session created", [
                'session_id' => $sessionId,
                'payment_key' => $paymentKey,
                'amount' => $paymentData['amount'] ?? null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create payment session", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getPaymentSession(string $sessionId): ?array
    {
        try {
            $paymentKey = self::PAYMENT_PREFIX . $sessionId;
            $sessionData = Redis::get($paymentKey);

            if (!$sessionData) {
                return null;
            }

            return json_decode($sessionData, true);
        } catch (\Exception $e) {
            Log::error("Failed to get payment session", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function updatePaymentSession(string $sessionId, array $updates): bool
    {
        try {
            $paymentKey = self::PAYMENT_PREFIX . $sessionId;
            $existingData = $this->getPaymentSession($sessionId);

            if (!$existingData) {
                return false;
            }

            $updatedData = array_merge($existingData, $updates);
            $updatedData['updated_at'] = now()->toISOString();

            $ttl = Redis::ttl($paymentKey);
            if ($ttl > 0) {
                Redis::setex($paymentKey, $ttl, json_encode($updatedData));
            } else {
                Redis::setex($paymentKey, self::PAYMENT_EXPIRY, json_encode($updatedData));
            }

            Log::info("Payment session updated", [
                'session_id' => $sessionId,
                'updates' => array_keys($updates)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update payment session", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deletePaymentSession(string $sessionId): bool
    {
        try {
            $paymentKey = self::PAYMENT_PREFIX . $sessionId;
            $result = Redis::del($paymentKey);

            Log::info("Payment session deleted", [
                'session_id' => $sessionId,
                'deleted' => $result
            ]);

            return $result > 0;
        } catch (\Exception $e) {
            Log::error("Failed to delete payment session", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function extendPaymentSession(string $sessionId, int $seconds = 300): bool
    {
        try {
            $paymentKey = self::PAYMENT_PREFIX . $sessionId;
            $exists = Redis::exists($paymentKey);

            if (!$exists) {
                return false;
            }

            Redis::expire($paymentKey, $seconds);

            $sessionData = $this->getPaymentSession($sessionId);
            if ($sessionData) {
                $sessionData['expires_at'] = now()->addSeconds($seconds)->toISOString();
                Redis::setex($paymentKey, $seconds, json_encode($sessionData));
            }

            Log::info("Payment session extended", [
                'session_id' => $sessionId,
                'extended_by' => $seconds
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to extend payment session", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function isPaymentSessionValid(string $sessionId): bool
    {
        try {
            $paymentKey = self::PAYMENT_PREFIX . $sessionId;
            return Redis::exists($paymentKey) > 0;
        } catch (\Exception $e) {
            Log::error("Failed to check payment session validity", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getPaymentSessionTTL(string $sessionId): int
    {
        try {
            $paymentKey = self::PAYMENT_PREFIX . $sessionId;
            return Redis::ttl($paymentKey);
        } catch (\Exception $e) {
            Log::error("Failed to get payment session TTL", [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return -1;
        }
    }
}
