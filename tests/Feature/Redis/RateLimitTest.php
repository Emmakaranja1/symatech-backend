<?php

namespace Tests\Feature\Redis;

use Tests\TestCase;
use App\Services\Redis\RateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $rateLimitService;
    protected $testIdentifier = 'test_user_123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimitService = app(RateLimitService::class);
    }

    public function test_can_check_rate_limit()
    {
        $response = $this->postJson('/api/redis/rate-limit/check', [
            'identifier' => $this->testIdentifier,
            'max_attempts' => 5,
            'decay_seconds' => 60
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'allowed' => true,
                        'attempts' => 1,
                        'remaining' => 4
                    ]
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('reset_time', $data);
        $this->assertArrayHasKey('key', $data);
        $this->assertArrayHasKey('ttl', $data);
    }

    public function test_rate_limit_blocks_after_max_attempts()
    {
        $maxAttempts = 3;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $response = $this->postJson('/api/redis/rate-limit/check', [
                'identifier' => $this->testIdentifier,
                'max_attempts' => $maxAttempts,
                'decay_seconds' => 60
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'success' => true,
                        'data' => [
                            'allowed' => true,
                            'attempts' => $i,
                            'remaining' => $maxAttempts - $i
                        ]
                    ]);
        }

        $response = $this->postJson('/api/redis/rate-limit/check', [
            'identifier' => $this->testIdentifier,
            'max_attempts' => $maxAttempts,
            'decay_seconds' => 60
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'allowed' => false,
                        'attempts' => $maxAttempts + 1,
                        'remaining' => 0
                    ]
                ]);
    }

    public function test_can_clear_rate_limit()
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->rateLimitService->incrementRateLimit($this->testIdentifier);
        }

        $response = $this->deleteJson('/api/redis/rate-limit/clear', [
            'identifier' => $this->testIdentifier
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Rate limit cleared successfully'
                ]);

        $status = $this->rateLimitService->getRateLimitStatus($this->testIdentifier);
        $this->assertNull($status);
    }

    public function test_can_get_rate_limit_status()
    {
        $this->rateLimitService->incrementRateLimit($this->testIdentifier);

        $response = $this->getJson('/api/redis/rate-limit/status?identifier=' . $this->testIdentifier);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['attempts']);
        $this->assertArrayHasKey('ttl', $data);
        $this->assertArrayHasKey('key', $data);
    }

    public function test_can_increment_rate_limit()
    {
        $response = $this->postJson('/api/redis/rate-limit/increment', [
            'identifier' => $this->testIdentifier
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'identifier' => $this->testIdentifier,
                        'attempts' => 1
                    ]
                ]);

        $response = $this->postJson('/api/redis/rate-limit/increment', [
            'identifier' => $this->testIdentifier
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'identifier' => $this->testIdentifier,
                        'attempts' => 2
                    ]
                ]);
    }

    public function test_can_check_multiple_rate_limits()
    {
        $limits = [
            [
                'name' => 'api_calls',
                'identifier' => 'user_123_api',
                'max_attempts' => 10,
                'decay_seconds' => 60
            ],
            [
                'name' => 'login_attempts',
                'identifier' => 'user_123_login',
                'max_attempts' => 3,
                'decay_seconds' => 300
            ],
            [
                'name' => 'file_uploads',
                'identifier' => 'user_123_upload',
                'max_attempts' => 5,
                'decay_seconds' => 3600
            ]
        ];

        $response = $this->postJson('/api/redis/rate-limit/multiple', [
            'limits' => $limits
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('api_calls', $data);
        $this->assertArrayHasKey('login_attempts', $data);
        $this->assertArrayHasKey('file_uploads', $data);

        foreach ($data as $name => $result) {
            $this->assertEquals(1, $result['attempts']);
            $this->assertTrue($result['allowed']);
        }
    }

    public function test_can_check_if_blocked()
    {
        $maxAttempts = 2;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $this->rateLimitService->incrementRateLimit($this->testIdentifier);
        }

        $response = $this->getJson('/api/redis/rate-limit/blocked?identifier=' . $this->testIdentifier . '&max_attempts=' . $maxAttempts . '&decay_seconds=60');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'identifier' => $this->testIdentifier,
                        'is_blocked' => false,
                        'max_attempts' => $maxAttempts
                    ]
                ]);

        $this->rateLimitService->incrementRateLimit($this->testIdentifier);

        $response = $this->getJson('/api/redis/rate-limit/blocked?identifier=' . $this->testIdentifier . '&max_attempts=' . $maxAttempts . '&decay_seconds=60');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'identifier' => $this->testIdentifier,
                        'is_blocked' => true,
                        'max_attempts' => $maxAttempts
                    ]
                ]);
    }

    public function test_rate_limiting_functionality()
    {
        $response = $this->getJson('/api/redis/rate-limit/test');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Rate limiting test completed'
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('test_identifier', $data);
        $this->assertArrayHasKey('max_attempts', $data);
        $this->assertArrayHasKey('decay_seconds', $data);
        $this->assertArrayHasKey('results', $data);

        $results = $data['results'];
        $this->assertCount(7, $results);

        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue($results[$i]['allowed']);
            $this->assertEquals($i + 1, $results[$i]['attempts']);
            $this->assertEquals(5 - $i - 1, $results[$i]['remaining']);
        }

        for ($i = 5; $i < 7; $i++) {
            $this->assertFalse($results[$i]['allowed']);
            $this->assertEquals($i + 1, $results[$i]['attempts']);
            $this->assertEquals(0, $results[$i]['remaining']);
        }
    }

    public function test_rate_limit_validation()
    {
        $response = $this->postJson('/api/redis/rate-limit/check', [
            'identifier' => '',
            'max_attempts' => 0,
            'decay_seconds' => -1
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);
    }

    public function test_nonexistent_rate_limit_status()
    {
        $response = $this->getJson('/api/redis/rate-limit/status?identifier=nonexistent');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'No rate limit found for this identifier'
                ]);
    }
}
