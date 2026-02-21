<?php

namespace Tests\Feature\Redis;

use Tests\TestCase;
use App\Services\Redis\RedisConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RedisConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected $redisConnectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redisConnectionService = app(RedisConnectionService::class);
    }

    public function test_redis_connection_test()
    {
        $response = $this->getJson('/api/redis/connection/test');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Redis connection is stable and operational'
                ]);

        $data = $response->json('data');
        $this->assertEquals('connected', $data['status']);
        $this->assertArrayHasKey('response_time_ms', $data);
        $this->assertArrayHasKey('memory_usage', $data);
        $this->assertArrayHasKey('connected_clients', $data);
        $this->assertArrayHasKey('redis_version', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertTrue($data['success']);
    }

    public function test_redis_basic_operations()
    {
        $response = $this->getJson('/api/redis/connection/operations');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Redis basic operations test passed'
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('operations', $data);
        $this->assertArrayHasKey('set_time', $data['operations']);
        $this->assertArrayHasKey('get_time', $data['operations']);
        $this->assertArrayHasKey('expire_time', $data['operations']);
        $this->assertArrayHasKey('delete_time', $data['operations']);
        $this->assertTrue($data['operations']['test_passed']);
        $this->assertArrayHasKey('total_time', $data['operations']);
    }

    public function test_redis_hash_operations()
    {
        $response = $this->getJson('/api/redis/connection/hash');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Redis hash operations test passed'
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('operations', $data);
        $this->assertArrayHasKey('hmset_time', $data['operations']);
        $this->assertArrayHasKey('hgetall_time', $data['operations']);
        $this->assertArrayHasKey('hdel_time', $data['operations']);
        $this->assertTrue($data['operations']['test_passed']);
        $this->assertArrayHasKey('total_time', $data['operations']);
    }

    public function test_redis_info()
    {
        $response = $this->getJson('/api/redis/connection/info');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Redis server information retrieved successfully'
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('info', $data);
        
        $info = $data['info'];
        $this->assertArrayHasKey('redis_version', $info);
        $this->assertArrayHasKey('redis_mode', $info);
        $this->assertArrayHasKey('os', $info);
        $this->assertArrayHasKey('arch_bits', $info);
        $this->assertArrayHasKey('uptime_in_seconds', $info);
        $this->assertArrayHasKey('connected_clients', $info);
        $this->assertArrayHasKey('used_memory_human', $info);
        $this->assertArrayHasKey('total_commands_processed', $info);
    }

    public function test_redis_monitor_connection()
    {
        $response = $this->getJson('/api/redis/connection/monitor');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All Redis tests passed - connection is stable and operational'
                ]);

        $data = $response->json('data');
        $this->assertTrue($data['overall_status']);
        $this->assertArrayHasKey('connection', $data);
        $this->assertArrayHasKey('basic_operations', $data);
        $this->assertArrayHasKey('hash_operations', $data);
        $this->assertArrayHasKey('server_info', $data);
        $this->assertArrayHasKey('timestamp', $data);

        $this->assertTrue($data['connection']['success']);
        $this->assertTrue($data['basic_operations']['success']);
        $this->assertTrue($data['hash_operations']['success']);
        $this->assertTrue($data['server_info']['success']);
    }

    public function test_redis_health_check()
    {
        $response = $this->getJson('/api/redis/connection/health');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Redis is healthy'
                ]);

        $data = $response->json('data');
        $this->assertEquals('healthy', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('response_time_ms', $data);
        $this->assertArrayHasKey('checks', $data);
        $this->assertTrue($data['checks']['connection']);
    }

    public function test_redis_connection_stability()
    {
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/redis/connection/test');
            $responses[] = $response->json();
            usleep(100000); // 100ms delay between requests
        }

        foreach ($responses as $response) {
            $this->assertTrue($response['success']);
            $this->assertEquals('connected', $response['data']['status']);
            $this->assertLessThan(100, $response['data']['response_time_ms']); // Should be under 100ms
        }
    }

    public function test_redis_service_direct_methods()
    {
        $connectionTest = $this->redisConnectionService->testConnection();
        $this->assertTrue($connectionTest['success']);
        $this->assertEquals('connected', $connectionTest['status']);

        $operationsTest = $this->redisConnectionService->testBasicOperations();
        $this->assertTrue($operationsTest['success']);
        $this->assertTrue($operationsTest['operations']['test_passed']);

        $hashTest = $this->redisConnectionService->testHashOperations();
        $this->assertTrue($hashTest['success']);
        $this->assertTrue($hashTest['operations']['test_passed']);

        $info = $this->redisConnectionService->getRedisInfo();
        $this->assertTrue($info['success']);
        $this->assertArrayHasKey('info', $info);
    }

    public function test_redis_connection_performance()
    {
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $this->redisConnectionService->testConnection();
        }
        
        $endTime = microtime(true);
        $averageTime = (($endTime - $startTime) / 10) * 1000; // Convert to milliseconds

        $this->assertLessThan(50, $averageTime, 'Average connection test time should be under 50ms');
    }

    public function test_redis_memory_usage_tracking()
    {
        $connectionTest = $this->redisConnectionService->testConnection();
        $this->assertArrayHasKey('memory_usage', $connectionTest);
        $this->assertIsString($connectionTest['memory_usage']);
        
        $info = $this->redisConnectionService->getRedisInfo();
        $this->assertArrayHasKey('used_memory_human', $info['info']);
        $this->assertArrayHasKey('used_memory_peak_human', $info['info']);
    }
}
