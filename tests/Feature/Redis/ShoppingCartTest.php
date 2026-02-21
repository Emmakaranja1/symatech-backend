<?php

namespace Tests\Feature\Redis;

use Tests\TestCase;
use App\Services\Redis\ShoppingCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShoppingCartTest extends TestCase
{
    use RefreshDatabase;

    protected $cartService;
    protected $userId = 123;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(ShoppingCartService::class);
    }

    public function test_can_add_item_to_cart()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $response = $this->postJson('/api/redis/cart/add', [
            'user_id' => $this->userId,
            'item' => $item
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Item added to cart successfully'
                ]);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertArrayHasKey('1', $cart);
        $this->assertEquals('Test Product', $cart['1']['name']);
    }

    public function test_can_retrieve_cart()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $this->cartService->addItem($this->userId, $item);

        $response = $this->getJson('/api/redis/cart?user_id=' . $this->userId);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user_id' => $this->userId,
                        'count' => 1,
                        'total' => 59.98
                    ]
                ]);
    }

    public function test_can_remove_item_from_cart()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $this->cartService->addItem($this->userId, $item);

        $response = $this->deleteJson('/api/redis/cart/item', [
            'user_id' => $this->userId,
            'item_id' => 1
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Item removed from cart successfully'
                ]);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertEmpty($cart);
    }

    public function test_can_update_item_quantity()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $this->cartService->addItem($this->userId, $item);

        $response = $this->putJson('/api/redis/cart/quantity', [
            'user_id' => $this->userId,
            'item_id' => 1,
            'quantity' => 5
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Item quantity updated successfully'
                ]);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertEquals(5, $cart['1']['quantity']);
    }

    public function test_can_clear_cart()
    {
        $item1 = [
            'id' => 1,
            'name' => 'Test Product 1',
            'price' => 29.99,
            'quantity' => 2
        ];

        $item2 = [
            'id' => 2,
            'name' => 'Test Product 2',
            'price' => 19.99,
            'quantity' => 1
        ];

        $this->cartService->addItem($this->userId, $item1);
        $this->cartService->addItem($this->userId, $item2);

        $response = $this->deleteJson('/api/redis/cart', [
            'user_id' => $this->userId
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Cart cleared successfully'
                ]);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertEmpty($cart);
    }

    public function test_can_get_cart_summary()
    {
        $item1 = [
            'id' => 1,
            'name' => 'Test Product 1',
            'price' => 29.99,
            'quantity' => 2
        ];

        $item2 = [
            'id' => 2,
            'name' => 'Test Product 2',
            'price' => 19.99,
            'quantity' => 1
        ];

        $this->cartService->addItem($this->userId, $item1);
        $this->cartService->addItem($this->userId, $item2);

        $response = $this->getJson('/api/redis/cart/summary?user_id=' . $this->userId);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user_id' => $this->userId,
                        'item_count' => 2,
                        'total_amount' => 79.97
                    ]
                ]);
    }

    public function test_cart_persistence_in_redis()
    {
        $item = [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'quantity' => 2
        ];

        $this->cartService->addItem($this->userId, $item);

        $cart = $this->cartService->getCart($this->userId);
        $this->assertNotEmpty($cart);

        $this->assertEquals(1, $this->cartService->getCartCount($this->userId));
        $this->assertEquals(59.98, $this->cartService->getCartTotal($this->userId));
    }

    public function test_add_item_validation()
    {
        $response = $this->postJson('/api/redis/cart/add', [
            'user_id' => 'invalid',
            'item' => []
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);
    }
}
