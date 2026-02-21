<?php

namespace App\Services\Redis;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ShoppingCartService
{
    private const CART_PREFIX = 'cart:';
    private const CART_EXPIRY = 86400; // 24 hours

    public function addItem(int $userId, array $item): bool
    {
        try {
            $cartKey = self::CART_PREFIX . $userId;
            $itemId = $item['id'];
            $itemData = json_encode($item);

            Redis::hset($cartKey, $itemId, $itemData);
            Redis::expire($cartKey, self::CART_EXPIRY);

            Log::info("Item added to cart", [
                'user_id' => $userId,
                'item_id' => $itemId,
                'cart_key' => $cartKey
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to add item to cart", [
                'user_id' => $userId,
                'item_id' => $item['id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function removeItem(int $userId, int $itemId): bool
    {
        try {
            $cartKey = self::CART_PREFIX . $userId;
            $result = Redis::hdel($cartKey, $itemId);

            Log::info("Item removed from cart", [
                'user_id' => $userId,
                'item_id' => $itemId,
                'removed' => $result
            ]);

            return $result > 0;
        } catch (\Exception $e) {
            Log::error("Failed to remove item from cart", [
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getCart(int $userId): array
    {
        try {
            $cartKey = self::CART_PREFIX . $userId;
            $cartItems = Redis::hgetall($cartKey);

            $items = [];
            foreach ($cartItems as $itemId => $itemData) {
                $items[$itemId] = json_decode($itemData, true);
            }

            return $items;
        } catch (\Exception $e) {
            Log::error("Failed to get cart", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function clearCart(int $userId): bool
    {
        try {
            $cartKey = self::CART_PREFIX . $userId;
            $result = Redis::del($cartKey);

            Log::info("Cart cleared", [
                'user_id' => $userId,
                'deleted' => $result
            ]);

            return $result > 0;
        } catch (\Exception $e) {
            Log::error("Failed to clear cart", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function updateItemQuantity(int $userId, int $itemId, int $quantity): bool
    {
        try {
            $cartKey = self::CART_PREFIX . $userId;
            $itemData = Redis::hget($cartKey, $itemId);

            if (!$itemData) {
                return false;
            }

            $item = json_decode($itemData, true);
            $item['quantity'] = $quantity;

            Redis::hset($cartKey, $itemId, json_encode($item));
            Redis::expire($cartKey, self::CART_EXPIRY);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update item quantity", [
                'user_id' => $userId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getCartCount(int $userId): int
    {
        try {
            $cartKey = self::CART_PREFIX . $userId;
            return Redis::hlen($cartKey);
        } catch (\Exception $e) {
            Log::error("Failed to get cart count", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    public function getCartTotal(int $userId): float
    {
        try {
            $cartItems = $this->getCart($userId);
            $total = 0;

            foreach ($cartItems as $item) {
                $price = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $total += ($price * $quantity);
            }

            return (float) $total;
        } catch (\Exception $e) {
            Log::error("Failed to calculate cart total", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }
}
