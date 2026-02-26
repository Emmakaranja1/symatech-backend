<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Add item to cart
     */
    public function addToCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');
            $productId = $request->input('product_id');
            $quantity = $request->input('quantity');

            // Check if product exists and has enough stock
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            if ($product->stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock',
                    'available_stock' => $product->stock
                ], 400);
            }

            // Redis cart key
            $cartKey = "cart:user:{$userId}";
            
            // Get current cart
            $cart = Redis::hgetall($cartKey);
            
            // Check if product already in cart
            $currentQuantity = isset($cart[$productId]) ? (int)$cart[$productId] : 0;
            $newQuantity = $currentQuantity + $quantity;

            // Check if new quantity exceeds stock
            if ($product->stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add more items than available stock',
                    'available_stock' => $product->stock,
                    'current_cart_quantity' => $currentQuantity
                ], 400);
            }

            // Add/update item in cart
            Redis::hset($cartKey, $productId, $newQuantity);
            
            // Set expiration (24 hours)
            Redis::expire($cartKey, 86400);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => [
                    'product_id' => $productId,
                    'quantity' => $newQuantity,
                    'cart_total_items' => array_sum(Redis::hgetall($cartKey))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCartItemQuantity(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');
            $productId = $request->input('product_id');
            $quantity = $request->input('quantity');

            // Check if product exists and has enough stock
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Redis cart key
            $cartKey = "cart:user:{$userId}";
            
            // Get current cart
            $cart = Redis::hgetall($cartKey);

            if ($quantity == 0) {
                // Remove item from cart
                Redis::hdel($cartKey, $productId);
                $message = 'Item removed from cart';
            } else {
                // Check if new quantity exceeds stock
                if ($product->stock < $quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock',
                        'available_stock' => $product->stock
                    ], 400);
                }

                // Update quantity
                Redis::hset($cartKey, $productId, $quantity);
                $message = 'Cart item quantity updated';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'cart_total_items' => array_sum(Redis::hgetall($cartKey))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'product_id' => 'required|integer|exists:products,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');
            $productId = $request->input('product_id');

            // Redis cart key
            $cartKey = "cart:user:{$userId}";
            
            // Remove item from cart
            Redis::hdel($cartKey, $productId);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully',
                'data' => [
                    'product_id' => $productId,
                    'cart_total_items' => array_sum(Redis::hgetall($cartKey))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item from cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');

            // Redis cart key
            $cartKey = "cart:user:{$userId}";
            
            // Clear cart
            Redis::del($cartKey);

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cart contents
     */
    public function getCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');

            // Redis cart key
            $cartKey = "cart:user:{$userId}";
            
            // Get cart items
            $cartItems = Redis::hgetall($cartKey);
            
            if (empty($cartItems)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart is empty',
                    'data' => [
                        'items' => [],
                        'total_items' => 0,
                        'total_amount' => 0
                    ]
                ]);
            }

            // Get product details for each cart item
            $items = [];
            $totalAmount = 0;

            foreach ($cartItems as $productId => $quantity) {
                $product = Product::find($productId);
                if ($product) {
                    $subtotal = $product->price * $quantity;
                    $items[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'title' => $product->title,
                        'sku' => $product->sku,
                        'price' => $product->price,
                        'formatted_price' => 'KES ' . number_format($product->price, 2),
                        'quantity' => (int)$quantity,
                        'subtotal' => $subtotal,
                        'formatted_subtotal' => 'KES ' . number_format($subtotal, 2),
                        'image' => $product->image,
                        'stock' => $product->stock,
                        'available' => $product->stock >= $quantity
                    ];
                    $totalAmount += $subtotal;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cart retrieved successfully',
                'data' => [
                    'items' => $items,
                    'total_items' => array_sum($cartItems),
                    'total_amount' => $totalAmount,
                    'formatted_total' => 'KES ' . number_format($totalAmount, 2)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cart count
     */
    public function getCartCount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = $request->input('user_id');

            // Redis cart key
            $cartKey = "cart:user:{$userId}";
            
            // Get cart items count
            $cartItems = Redis::hgetall($cartKey);
            $totalItems = array_sum($cartItems);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_items' => $totalItems
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cart count: ' . $e->getMessage()
            ], 500);
        }
    }
}
