<?php

namespace App\Services\Redis;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class UserPreferencesService
{
    private const PREF_PREFIX = 'preferences:';
    private const PREF_EXPIRY = 2592000; // 30 days

    public function setPreference(int $userId, string $key, $value): bool
    {
        try {
            $prefKey = self::PREF_PREFIX . $userId;
            $serializedValue = json_encode($value);

            Redis::hset($prefKey, $key, $serializedValue);
            Redis::expire($prefKey, self::PREF_EXPIRY);

            Log::info("User preference set", [
                'user_id' => $userId,
                'key' => $key,
                'pref_key' => $prefKey
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to set user preference", [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getPreference(int $userId, string $key, $default = null)
    {
        try {
            $prefKey = self::PREF_PREFIX . $userId;
            $value = Redis::hget($prefKey, $key);

            if ($value === null) {
                return $default;
            }

            return json_decode($value, true);
        } catch (\Exception $e) {
            Log::error("Failed to get user preference", [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    public function getAllPreferences(int $userId): array
    {
        try {
            $prefKey = self::PREF_PREFIX . $userId;
            $preferences = Redis::hgetall($prefKey);

            $decoded = [];
            foreach ($preferences as $key => $value) {
                $decoded[$key] = json_decode($value, true);
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error("Failed to get all user preferences", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function removePreference(int $userId, string $key): bool
    {
        try {
            $prefKey = self::PREF_PREFIX . $userId;
            $result = Redis::hdel($prefKey, $key);

            Log::info("User preference removed", [
                'user_id' => $userId,
                'key' => $key,
                'removed' => $result
            ]);

            return $result > 0;
        } catch (\Exception $e) {
            Log::error("Failed to remove user preference", [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function clearAllPreferences(int $userId): bool
    {
        try {
            $prefKey = self::PREF_PREFIX . $userId;
            $result = Redis::del($prefKey);

            Log::info("All user preferences cleared", [
                'user_id' => $userId,
                'deleted' => $result
            ]);

            return $result > 0;
        } catch (\Exception $e) {
            Log::error("Failed to clear all user preferences", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function setMultiplePreferences(int $userId, array $preferences): bool
    {
        try {
            $prefKey = self::PREF_PREFIX . $userId;
            $serializedPrefs = [];

            foreach ($preferences as $key => $value) {
                $serializedPrefs[$key] = json_encode($value);
            }

            if (!empty($serializedPrefs)) {
                Redis::hmset($prefKey, $serializedPrefs);
                Redis::expire($prefKey, self::PREF_EXPIRY);
            }

            Log::info("Multiple user preferences set", [
                'user_id' => $userId,
                'count' => count($preferences)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to set multiple user preferences", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
