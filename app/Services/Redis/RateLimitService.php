<?php

namespace App\Services\Redis;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    private const RATE_LIMIT_PREFIX = 'rate_limit:';
    
    public function checkRateLimit(string $identifier, int $maxAttempts, int $decaySeconds): array
    {
        try {
            $key = self::RATE_LIMIT_PREFIX . $identifier;
            $current = Redis::incr($key);

            if ($current === 1) {
                Redis::expire($key, $decaySeconds);
            }

            $ttl = Redis::ttl($key);
            $remaining = max(0, $maxAttempts - $current);
            $resetTime = now()->addSeconds($ttl)->timestamp;

            $result = [
                'allowed' => $current <= $maxAttempts,
                'attempts' => $current,
                'remaining' => $remaining,
                'reset_time' => $resetTime,
                'key' => $key,
                'ttl' => $ttl
            ];

            Log::info("Rate limit check", [
                'identifier' => $identifier,
                'attempts' => $current,
                'max_attempts' => $maxAttempts,
                'allowed' => $result['allowed']
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("Rate limit check failed", [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);

            return [
                'allowed' => true,
                'attempts' => 0,
                'remaining' => $maxAttempts,
                'reset_time' => now()->addSeconds($decaySeconds)->timestamp,
                'key' => self::RATE_LIMIT_PREFIX . $identifier,
                'ttl' => $decaySeconds,
                'error' => $e->getMessage()
            ];
        }
    }

    public function clearRateLimit(string $identifier): bool
    {
        try {
            $key = self::RATE_LIMIT_PREFIX . $identifier;
            $result = Redis::del($key);

            Log::info("Rate limit cleared", [
                'identifier' => $identifier,
                'deleted' => $result
            ]);

            return $result > 0;
        } catch (\Exception $e) {
            Log::error("Failed to clear rate limit", [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getRateLimitStatus(string $identifier): ?array
    {
        try {
            $key = self::RATE_LIMIT_PREFIX . $identifier;
            $attempts = Redis::get($key);
            $ttl = Redis::ttl($key);

            if ($attempts === null) {
                return null;
            }

            return [
                'attempts' => (int) $attempts,
                'ttl' => $ttl,
                'key' => $key
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get rate limit status", [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function incrementRateLimit(string $identifier): int
    {
        try {
            $key = self::RATE_LIMIT_PREFIX . $identifier;
            return Redis::incr($key);
        } catch (\Exception $e) {
            Log::error("Failed to increment rate limit", [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function setRateLimitExpiry(string $identifier, int $seconds): bool
    {
        try {
            $key = self::RATE_LIMIT_PREFIX . $identifier;
            return Redis::expire($key, $seconds);
        } catch (\Exception $e) {
            Log::error("Failed to set rate limit expiry", [
                'identifier' => $identifier,
                'seconds' => $seconds,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function checkMultipleRateLimits(array $limits): array
    {
        $results = [];
        
        foreach ($limits as $name => $config) {
            $identifier = $config['identifier'];
            $maxAttempts = $config['max_attempts'];
            $decaySeconds = $config['decay_seconds'];
            
            $results[$name] = $this->checkRateLimit($identifier, $maxAttempts, $decaySeconds);
        }

        return $results;
    }

    public function isBlocked(string $identifier, int $maxAttempts, int $decaySeconds): bool
    {
        $result = $this->checkRateLimit($identifier, $maxAttempts, $decaySeconds);
        return !$result['allowed'];
    }
}
