<?php

namespace Tests\Unit\Redis;

use Tests\TestCase;
use App\Services\Redis\ShoppingCartService;
use App\Services\Redis\UserPreferencesService;
use App\Services\Redis\PaymentSessionService;
use App\Services\Redis\RateLimitService;
use App\Services\Redis\RedisConnectionService;

class RedisServicesTest extends TestCase
{
    protected $cartService;
    protected $preferencesService;
    protected $paymentSessionService;
    protected $rateLimitService;
    protected $redisConnectionService;
    protected $userId = 123;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(ShoppingCartService::class);
        $this->preferencesService = app(UserPreferencesService::class);
        $this->paymentSessionService = app(PaymentSessionService::class);
        $this->rateLimitService = app(RateLimitService::class);
        $this->redisConnectionService = app(RedisConnectionService::class);
    }

    public function test_shopping_cart_service_can_add_and_retrieve_items()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $result = $this->cartService->addItem($this->userId, $item);
        $this->assertTrue($result);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertArrayHasKey('1', $cart);
        $this->assertEquals('Test Product', $cart['1']['name']);
        $this->assertEquals(29.99, $cart['1']['price']);
        $this->assertEquals(2, $cart['1']['quantity']);
    }

    public function test_shopping_cart_persistence()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $this->cartService->addItem($this->userId, $item);
        
        $count = $this->cartService->getCartCount($this->userId);
        $this->assertEquals(1, $count);

        $total = $this->cartService->getCartTotal($this->userId);
        $this->assertEquals(59.98, $total);
    }

    public function test_shopping_cart_can_update_quantity()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $this->cartService->addItem($this->userId, $item);
        
        $result = $this->cartService->updateItemQuantity($this->userId, 1, 5);
        $this->assertTrue($result);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertEquals(5, $cart['1']['quantity']);
    }

    public function test_shopping_cart_can_remove_items()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $this->cartService->addItem($this->userId, $item);
        
        $result = $this->cartService->removeItem($this->userId, 1);
        $this->assertTrue($result);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertEmpty($cart);
    }

    public function test_user_preferences_can_set_and_get_values()
    {
        $result = $this->preferencesService->setPreference($this->userId, 'theme', 'dark');
        $this->assertTrue($result);

        $value = $this->preferencesService->getPreference($this->userId, 'theme');
        $this->assertEquals('dark', $value);
    }

    public function test_user_preferences_storage_and_retrieval()
    {
        $complexData = [
            'dashboard_layout' => [
                'widgets' => ['sales', 'orders', 'users'],
                'positions' => ['top', 'middle', 'bottom']
            ],
            'settings' => [
                'auto_refresh' => true,
                'refresh_interval' => 30
            ]
        ];

        $this->preferencesService->setPreference($this->userId, 'ui_config', $complexData);
        
        $retrievedData = $this->preferencesService->getPreference($this->userId, 'ui_config');
        $this->assertEquals($complexData, $retrievedData);
    }

    public function test_user_preferences_can_get_all()
    {
        $preferences = [
            'theme' => 'dark',
            'language' => 'en',
            'notifications' => true
        ];

        foreach ($preferences as $key => $value) {
            $this->preferencesService->setPreference($this->userId, $key, $value);
        }

        $allPrefs = $this->preferencesService->getAllPreferences($this->userId);
        $this->assertEquals('dark', $allPrefs['theme']);
        $this->assertEquals('en', $allPrefs['language']);
        $this->assertEquals(true, $allPrefs['notifications']);
    }

    public function test_user_preferences_can_set_multiple()
    {
        $preferences = [
            'theme' => 'light',
            'language' => 'es',
            'timezone' => 'UTC'
        ];

        $result = $this->preferencesService->setMultiplePreferences($this->userId, $preferences);
        $this->assertTrue($result);

        $this->assertEquals('light', $this->preferencesService->getPreference($this->userId, 'theme'));
        $this->assertEquals('es', $this->preferencesService->getPreference($this->userId, 'language'));
        $this->assertEquals('UTC', $this->preferencesService->getPreference($this->userId, 'timezone'));
    }

    public function test_payment_session_can_create_and_retrieve()
    {
        $sessionId = 'test-session-' . time();
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => $this->userId,
            'payment_method' => 'credit_card',
            'description' => 'Test payment'
        ];

        $result = $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);
        $this->assertTrue($result);

        $session = $this->paymentSessionService->getPaymentSession($sessionId);
        $this->assertNotNull($session);
        $this->assertEquals(100.00, $session['amount']);
        $this->assertEquals('USD', $session['currency']);
        $this->assertEquals('pending', $session['status']);
        $this->assertArrayHasKey('created_at', $session);
        $this->assertArrayHasKey('expires_at', $session);
    }

    public function test_payment_session_temporary_storage()
    {
        $sessionId = 'temp-session-' . time();
        $paymentData = [
            'amount' => 50.00,
            'currency' => 'EUR',
            'user_id' => $this->userId,
            'payment_method' => 'paypal',
            'metadata' => [
                'order_id' => 'ORD-123',
                'customer_email' => 'test@example.com'
            ]
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);
        
        $retrievedSession = $this->paymentSessionService->getPaymentSession($sessionId);
        
        $this->assertNotNull($retrievedSession);
        $this->assertEquals(50.00, $retrievedSession['amount']);
        $this->assertEquals('EUR', $retrievedSession['currency']);
        $this->assertEquals('paypal', $retrievedSession['payment_method']);
        $this->assertEquals('ORD-123', $retrievedSession['metadata']['order_id']);
        $this->assertEquals('test@example.com', $retrievedSession['metadata']['customer_email']);
    }

    public function test_payment_session_can_update()
    {
        $sessionId = 'test-session-' . time();
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => $this->userId,
            'payment_method' => 'credit_card'
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);

        $updates = [
            'status' => 'processing',
            'transaction_id' => 'txn_123456'
        ];

        $result = $this->paymentSessionService->updatePaymentSession($sessionId, $updates);
        $this->assertTrue($result);

        $updatedSession = $this->paymentSessionService->getPaymentSession($sessionId);
        $this->assertEquals('processing', $updatedSession['status']);
        $this->assertEquals('txn_123456', $updatedSession['transaction_id']);
    }

    public function test_payment_session_validity()
    {
        $sessionId = 'test-session-' . time();
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => $this->userId,
            'payment_method' => 'credit_card'
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);
        
        $isValid = $this->paymentSessionService->isPaymentSessionValid($sessionId);
        $this->assertTrue($isValid);

        $ttl = $this->paymentSessionService->getPaymentSessionTTL($sessionId);
        $this->assertGreaterThan(0, $ttl);
    }

    public function test_rate_limiting_functionality()
    {
        $identifier = 'test_user_' . time();
        $maxAttempts = 3;
        $decaySeconds = 60;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $result = $this->rateLimitService->checkRateLimit($identifier, $maxAttempts, $decaySeconds);
            $this->assertTrue($result['allowed']);
            $this->assertEquals($i, $result['attempts']);
            $this->assertEquals($maxAttempts - $i, $result['remaining']);
        }

        $result = $this->rateLimitService->checkRateLimit($identifier, $maxAttempts, $decaySeconds);
        $this->assertFalse($result['allowed']);
        $this->assertEquals($maxAttempts + 1, $result['attempts']);
        $this->assertEquals(0, $result['remaining']);
    }

    public function test_rate_limit_can_clear()
    {
        $identifier = 'test_user_' . time();
        
        $this->rateLimitService->incrementRateLimit($identifier);
        $this->rateLimitService->incrementRateLimit($identifier);

        $status = $this->rateLimitService->getRateLimitStatus($identifier);
        $this->assertNotNull($status);
        $this->assertEquals(2, $status['attempts']);

        $result = $this->rateLimitService->clearRateLimit($identifier);
        $this->assertTrue($result);

        $status = $this->rateLimitService->getRateLimitStatus($identifier);
        $this->assertNull($status);
    }

    public function test_rate_limit_multiple_limits()
    {
        $limits = [
            'api_calls' => [
                'identifier' => 'user_123_api',
                'max_attempts' => 10,
                'decay_seconds' => 60
            ],
            'login_attempts' => [
                'identifier' => 'user_123_login',
                'max_attempts' => 3,
                'decay_seconds' => 300
            ]
        ];

        $results = $this->rateLimitService->checkMultipleRateLimits($limits);
        
        $this->assertArrayHasKey('api_calls', $results);
        $this->assertArrayHasKey('login_attempts', $results);
        
        foreach ($results as $name => $result) {
            $this->assertEquals(1, $result['attempts']);
            $this->assertTrue($result['allowed']);
        }
    }

    public function test_rate_limit_blocked_check()
    {
        $identifier = 'test_user_' . time();
        $maxAttempts = 2;

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $this->rateLimitService->incrementRateLimit($identifier);
        }

        $isBlocked = $this->rateLimitService->isBlocked($identifier, $maxAttempts, 60);
        $this->assertFalse($isBlocked);

        $this->rateLimitService->incrementRateLimit($identifier);
        
        $isBlocked = $this->rateLimitService->isBlocked($identifier, $maxAttempts, 60);
        $this->assertTrue($isBlocked);
    }

    public function test_redis_connection_stability()
    {
        $result = $this->redisConnectionService->testConnection();
        $this->assertTrue($result['success']);
        $this->assertEquals('connected', $result['status']);
        $this->assertArrayHasKey('response_time_ms', $result);
        $this->assertArrayHasKey('memory_usage', $result);
        $this->assertArrayHasKey('redis_version', $result);
    }

    public function test_redis_basic_operations()
    {
        $result = $this->redisConnectionService->testBasicOperations();
        $this->assertTrue($result['success']);
        $this->assertTrue($result['operations']['test_passed']);
        $this->assertArrayHasKey('set_time', $result['operations']);
        $this->assertArrayHasKey('get_time', $result['operations']);
        $this->assertArrayHasKey('total_time', $result['operations']);
    }

    public function test_redis_hash_operations()
    {
        $result = $this->redisConnectionService->testHashOperations();
        $this->assertTrue($result['success']);
        $this->assertTrue($result['operations']['test_passed']);
        $this->assertArrayHasKey('hmset_time', $result['operations']);
        $this->assertArrayHasKey('hgetall_time', $result['operations']);
        $this->assertArrayHasKey('total_time', $result['operations']);
    }

    public function test_redis_info()
    {
        $result = $this->redisConnectionService->getRedisInfo();
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('info', $result);
        
        $info = $result['info'];
        $this->assertArrayHasKey('redis_version', $info);
        $this->assertArrayHasKey('used_memory_human', $info);
        $this->assertArrayHasKey('connected_clients', $info);
    }

    public function test_redis_monitor_connection()
    {
        $result = $this->redisConnectionService->monitorConnection();
        $this->assertTrue($result['overall_status']);
        $this->assertTrue($result['connection']['success']);
        $this->assertTrue($result['basic_operations']['success']);
        $this->assertTrue($result['hash_operations']['success']);
        $this->assertTrue($result['server_info']['success']);
    }
}
