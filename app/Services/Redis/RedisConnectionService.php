<?php

namespace App\Services\Redis;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisConnectionService
{
    public function testConnection(): array
    {
        try {
            $startTime = microtime(true);
            
            Redis::ping();
            
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            $info = Redis::info();
            $memoryUsage = $info['used_memory_human'] ?? 'Unknown';
            $connectedClients = $info['connected_clients'] ?? 'Unknown';
            $redisVersion = $info['redis_version'] ?? 'Unknown';

            $result = [
                'status' => 'connected',
                'response_time_ms' => $responseTime,
                'memory_usage' => $memoryUsage,
                'connected_clients' => $connectedClients,
                'redis_version' => $redisVersion,
                'timestamp' => now()->toISOString(),
                'success' => true
            ];

            Log::info("Redis connection test successful", $result);

            return $result;
        } catch (\Exception $e) {
            $result = [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
                'success' => false
            ];

            Log::error("Redis connection test failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $result;
        }
    }

    public function testBasicOperations(): array
    {
        try {
            $testKey = 'redis_test_' . time();
            $testValue = 'test_value_' . uniqid();

            $results = [];

            $startTime = microtime(true);
            Redis::set($testKey, $testValue);
            $results['set_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $startTime = microtime(true);
            $retrievedValue = Redis::get($testKey);
            $results['get_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $startTime = microtime(true);
            Redis::expire($testKey, 60);
            $results['expire_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $startTime = microtime(true);
            Redis::del($testKey);
            $results['delete_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $results['test_passed'] = ($retrievedValue === $testValue);
            $results['total_time'] = array_sum($results);

            Log::info("Redis basic operations test", $results);

            return [
                'success' => true,
                'operations' => $results,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Redis basic operations test failed", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    public function getRedisInfo(): array
    {
        try {
            $info = Redis::info();
            
            $relevantInfo = [
                'redis_version' => $info['redis_version'] ?? 'Unknown',
                'redis_mode' => $info['redis_mode'] ?? 'Unknown',
                'os' => $info['os'] ?? 'Unknown',
                'arch_bits' => $info['arch_bits'] ?? 'Unknown',
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 'Unknown',
                'connected_clients' => $info['connected_clients'] ?? 'Unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'Unknown',
                'used_memory_peak_human' => $info['used_memory_peak_human'] ?? 'Unknown',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'Unknown',
                'keyspace_hits' => $info['keyspace_hits'] ?? 'Unknown',
                'keyspace_misses' => $info['keyspace_misses'] ?? 'Unknown',
            ];

            return [
                'success' => true,
                'info' => $relevantInfo,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get Redis info", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    public function testHashOperations(): array
    {
        try {
            $testHash = 'redis_hash_test_' . time();
            $testData = [
                'field1' => 'value1',
                'field2' => 'value2',
                'field3' => 'value3'
            ];

            $results = [];

            $startTime = microtime(true);
            Redis::hmset($testHash, $testData);
            $results['hmset_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $startTime = microtime(true);
            $retrievedData = Redis::hgetall($testHash);
            $results['hgetall_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $startTime = microtime(true);
            Redis::hdel($testHash, 'field1');
            $results['hdel_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $startTime = microtime(true);
            Redis::del($testHash);
            $results['delete_time'] = round((microtime(true) - $startTime) * 1000, 2);

            $results['test_passed'] = (count($retrievedData) === 3);
            $results['total_time'] = array_sum($results);

            Log::info("Redis hash operations test", $results);

            return [
                'success' => true,
                'operations' => $results,
                'timestamp' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error("Redis hash operations test failed", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    public function monitorConnection(): array
    {
        $connectionTest = $this->testConnection();
        $operationsTest = $this->testBasicOperations();
        $hashTest = $this->testHashOperations();
        $info = $this->getRedisInfo();

        return [
            'overall_status' => $connectionTest['success'] && $operationsTest['success'] && $hashTest['success'],
            'connection' => $connectionTest,
            'basic_operations' => $operationsTest,
            'hash_operations' => $hashTest,
            'server_info' => $info,
            'timestamp' => now()->toISOString()
        ];
    }
}
