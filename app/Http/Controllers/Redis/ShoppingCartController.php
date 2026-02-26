<?php

namespace App\Http\Controllers\Redis;

use App\Http\Controllers\Controller;
use App\Services\Redis\ShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ShoppingCartController extends Controller
{
    protected $cartService;

    public function __construct(ShoppingCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function addItem(Request $request): JsonResponse
    {
        // Require authentication
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }
        
        $userId = auth()->id();
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_FAILED'
            ], 422);
        }

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');

        try {
            // Get product information to include price
            $product = \App\Models\Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'error_code' => 'PRODUCT_NOT_FOUND'
                ], 404);
            }

            $success = $this->cartService->addItem($userId, [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product->price,
                'name' => $product->name
            ]);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item added to cart successfully',
                    'data' => [
                        'user_id' => $userId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $product->price
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add item to cart',
                    'error_code' => 'CART_OPERATION_FAILED'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cart operation failed',
                'error_code' => 'CART_EXCEPTION',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getCart(Request $request): JsonResponse
    {
        // Require authentication
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }
        
        $userId = auth()->id();
        $cart = $this->cartService->getCart($userId);
        $cartCount = $this->cartService->getCartCount($userId);
        $cartTotal = $this->cartService->getCartTotal($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'items' => $cart,
                'count' => $cartCount,
                'total' => $cartTotal
            ]
        ]);
    }

    public function removeItem(Request $request): JsonResponse
    {
        // Require authentication
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }
        
        $userId = auth()->id();
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $itemId = $request->input('product_id');

        $success = $this->cartService->removeItem($userId, $itemId);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove item from cart or item not found'
        ], 404);
    }

    public function updateQuantity(Request $request): JsonResponse
    {
        // Require authentication
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }
        
        $userId = auth()->id();
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $itemId = $request->input('product_id');
        $quantity = $request->input('quantity');

        $success = $this->cartService->updateItemQuantity($userId, $itemId, $quantity);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Item quantity updated successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update item quantity or item not found'
        ], 404);
    }

    public function clearCart(Request $request): JsonResponse
    {
        // Require authentication
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }
        
        $userId = auth()->id();
        $success = $this->cartService->clearCart($userId);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to clear cart or cart was already empty'
        ], 404);
    }

    public function getCartSummary(Request $request): JsonResponse
    {
        // Require authentication
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }
        
        $userId = auth()->id();
        $cartCount = $this->cartService->getCartCount($userId);
        $cartTotal = $this->cartService->getCartTotal($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'item_count' => $cartCount,
                'total_amount' => $cartTotal
            ]
        ]);
    }
}
