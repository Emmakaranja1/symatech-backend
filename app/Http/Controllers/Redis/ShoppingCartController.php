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
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'item' => 'required|array',
            'item.id' => 'required|integer',
            'item.name' => 'required|string|max:255',
            'item.price' => 'required|numeric|min:0',
            'item.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $item = $request->input('item');

        $success = $this->cartService->addItem($userId, $item);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => [
                    'user_id' => $userId,
                    'item' => $item
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to add item to cart'
        ], 500);
    }

    public function getCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
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
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'item_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $itemId = $request->input('item_id');

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
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $itemId = $request->input('item_id');
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
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
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
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
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
