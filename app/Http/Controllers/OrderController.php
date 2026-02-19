<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;

class OrderController extends Controller
{
      
public function adminIndex()
{
    $orders = Order::with('product', 'user')->get(); // fetch all orders, include user and product

    return response()->json($orders);
}

    // Existing store() and index() methods here...

    /**
     * Show a single order by ID for the authenticated user
     */
    public function show($id, Request $request)
    {
        $order = Order::with('product')
            ->where('id', $id)
            ->where('user_id', $request->user()->id) // ensure user can only see their own order
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order, 200);
    }

    // Store a new order
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);

        // Validate stock
        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock'], 400);
        }

        // Deduct stock
        $product->stock -= $request->quantity;
        $product->save();

        // Calculate total
        $totalPrice = $product->price * $request->quantity;

        // Save order
        $order = Order::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        // ✅ Log activity
        activity()
        ->causedBy($request->user())
        ->performedOn($order)
        ->withProperties([
            'product_id' => $product->id,
            'quantity' => $order->quantity,
            'total_price' => $order->total_price,
        ])
        ->log('Order created');

        return response()->json([
            'message' => 'Order placed successfully',
            'order_id' => $order->id,
            'product_name' => $product->name,
            'quantity' => $order->quantity,
            'total' => $order->total_price
        ], 201);
    }

    // List orders for logged-in user
    public function index(Request $request)
    {
        $orders = Order::with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json($orders, 200);
    }

    public function destroy($id, Request $request)
{
    $order = Order::find($id);

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    // Only allow deleting if the user owns the order or is admin
    if ($request->user()->id !== $order->user_id && $request->user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Restore stock
    $product = $order->product;
    $product->stock += $order->quantity;
    $product->save();

    $order->delete();

    return response()->json(['message' => 'Order deleted successfully'], 200);
}

}
