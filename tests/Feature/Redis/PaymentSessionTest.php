<?php

namespace Tests\Feature\Redis;

use Tests\TestCase;
use App\Services\Redis\PaymentSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentSessionTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentSessionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentSessionService = app(PaymentSessionService::class);
    }

    public function test_can_create_payment_session()
    {
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => 123,
            'payment_method' => 'credit_card',
            'description' => 'Test payment'
        ];

        $response = $this->postJson('/api/redis/payment/session/create', $paymentData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment session created successfully'
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('session_id', $data);
        $this->assertEquals('pending', $data['payment_data']['status']);
    }

    public function test_can_get_payment_session()
    {
        $sessionId = 'test-session-123';
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => 123,
            'payment_method' => 'credit_card',
            'status' => 'pending'
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);

        $response = $this->getJson('/api/redis/payment/session?session_id=' . $sessionId);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $data = $response->json('data');
        $this->assertEquals($sessionId, $data['session_id']);
        $this->assertEquals(100.00, $data['amount']);
        $this->assertEquals('pending', $data['status']);
    }

    public function test_can_update_payment_session()
    {
        $sessionId = 'test-session-123';
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => 123,
            'payment_method' => 'credit_card',
            'status' => 'pending'
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);

        $updates = [
            'status' => 'processing',
            'transaction_id' => 'txn_123456'
        ];

        $response = $this->putJson('/api/redis/payment/session/update', [
            'session_id' => $sessionId,
            'status' => 'processing',
            'transaction_id' => 'txn_123456'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment session updated successfully'
                ]);

        $updatedSession = $this->paymentSessionService->getPaymentSession($sessionId);
        $this->assertEquals('processing', $updatedSession['status']);
        $this->assertEquals('txn_123456', $updatedSession['transaction_id']);
    }

    public function test_can_delete_payment_session()
    {
        $sessionId = 'test-session-123';
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => 123,
            'payment_method' => 'credit_card'
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);

        $response = $this->deleteJson('/api/redis/payment/session', [
            'session_id' => $sessionId
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment session deleted successfully'
                ]);

        $session = $this->paymentSessionService->getPaymentSession($sessionId);
        $this->assertNull($session);
    }

    public function test_can_extend_payment_session()
    {
        $sessionId = 'test-session-123';
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => 123,
            'payment_method' => 'credit_card'
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);

        $response = $this->putJson('/api/redis/payment/session/extend', [
            'session_id' => $sessionId,
            'seconds' => 600
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment session extended successfully'
                ]);

        $ttl = $this->paymentSessionService->getPaymentSessionTTL($sessionId);
        $this->assertGreaterThan(300, $ttl);
    }

    public function test_can_check_session_validity()
    {
        $sessionId = 'test-session-123';
        $paymentData = [
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => 123,
            'payment_method' => 'credit_card'
        ];

        $this->paymentSessionService->createPaymentSession($sessionId, $paymentData);

        $response = $this->getJson('/api/redis/payment/session/validity?session_id=' . $sessionId);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'session_id' => $sessionId,
                        'is_valid' => true
                    ]
                ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('ttl_seconds', $data);
        $this->assertArrayHasKey('expires_at', $data);
    }

    public function test_payment_session_temporary_storage()
    {
        $sessionId = 'temp-session-' . time();
        $paymentData = [
            'amount' => 50.00,
            'currency' => 'EUR',
            'user_id' => 456,
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
        $this->assertArrayHasKey('created_at', $retrievedSession);
        $this->assertArrayHasKey('expires_at', $retrievedSession);
    }

    public function test_nonexistent_session_returns_404()
    {
        $response = $this->getJson('/api/redis/payment/session?session_id=nonexistent');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Payment session not found or expired'
                ]);
    }

    public function test_create_payment_session_validation()
    {
        $response = $this->postJson('/api/redis/payment/session/create', [
            'amount' => -10,
            'currency' => 'INVALID',
            'user_id' => 'invalid'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);
    }
}
