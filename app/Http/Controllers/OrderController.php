<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
      
public function adminIndex(Request $request)
{
    $query = Order::with(['product', 'user', 'latestPayment']);
    
    // Filter by date range if provided
    if ($request->has('start_date') && $request->has('end_date')) {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Validate date format
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);
        
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }
    
    // Filter by user if provided
    if ($request->has('user_id')) {
        $query->where('user_id', $request->input('user_id'));
    }
    
    // Filter by status if provided
    if ($request->has('status')) {
        $query->where('status', $request->input('status'));
    }

    // Filter by payment status if provided
    if ($request->has('payment_status')) {
        $query->where('payment_status', $request->input('payment_status'));
    }
    
    // Pagination
    $perPage = $request->input('per_page', 15);
    $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

    return response()->json($orders);
}

    // Existing store() and index() methods here...

    /**
     * Show a single order by ID for authenticated user
     */
    public function show($id, Request $request)
    {
        $order = Order::with(['product', 'payments'])
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
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required to place orders',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }

        try {
            $validated = $request->validate([
                'product_sku' => 'sometimes|required_without:product_id|string|exists:products,sku',
                'product_id' => 'sometimes|required_without:product_sku|integer|exists:products,id',
                'quantity' => 'required|integer|min:1|max:1000',
            ]);

            // Get product by SKU or ID
            if (isset($validated['product_sku'])) {
                $product = Product::where('sku', $validated['product_sku'])->first();
                $productIdentifier = $validated['product_sku'];
            } else {
                $product = Product::find($validated['product_id']);
                $productIdentifier = $validated['product_id'];
            }

            // Log the validated data for debugging
            \Log::info('Order request validated successfully', [
                'product_identifier' => $productIdentifier,
                'quantity' => $validated['quantity'],
                'user_id' => $request->user() ? $request->user()->id : 'null'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Order validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'received_data' => $request->all()
            ], 422);
        }

        // Validate stock and product availability
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND'
            ], 404);
        }

        if (!$product->active) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available for purchase',
                'error_code' => 'PRODUCT_INACTIVE'
            ], 400);
        }

        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'error_code' => 'INSUFFICIENT_STOCK',
                'available_stock' => $product->stock,
                'requested_quantity' => $validated['quantity']
            ], 400);
        }

        // Check for existing pending orders for the same product to prevent stock issues
        $existingPendingOrders = Order::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->where('payment_status', 'pending')
            ->sum('quantity');

        $totalRequestedQuantity = $existingPendingOrders + $validated['quantity'];
        if ($totalRequestedQuantity > $product->stock) {
            return response()->json([
                'success' => false,
                'message' => 'You have pending orders for this product. Total requested quantity exceeds available stock.',
                'error_code' => 'EXCEEDS_AVAILABLE_STOCK',
                'available_stock' => $product->stock,
                'pending_quantity' => $existingPendingOrders,
                'requested_quantity' => $validated['quantity']
            ], 400);
        }

        // Deduct stock
        $product->stock -= $validated['quantity'];
        $product->save();

        // Calculate total
        $totalPrice = $product->price * $validated['quantity'];

        // Save order
        $order = Order::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'total_price' => $totalPrice,
            'status' => 'pending',
            'payment_status' => 'pending',
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
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order->id,
            'product_name' => $product->name,
            'quantity' => $order->quantity,
            'total' => $order->total_price,
            'formatted_total' => 'KES ' . number_format($order->total_price, 2),
            'payment_status' => $order->payment_status,
            'order_status' => $order->status,
            'next_step' => 'Proceed to payment to complete your order',
            'payment_options' => [
                'mpesa' => [
                    'available' => true,
                    'endpoint' => '/api/payments/mpesa/initiate'
                ],
                'flutterwave' => [
                    'available' => true,
                    'endpoint' => '/api/payments/flutterwave/initiate'
                ]
            ],
            'created_at' => $order->created_at
        ], 201);
    }

    /**
     * Check stock availability for a product
     */
    public function checkStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_sku' => 'sometimes|required_without:product_id|string|exists:products,sku',
            'product_id' => 'sometimes|required_without:product_sku|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get product by SKU or ID
        if (isset($request->product_sku)) {
            $product = Product::where('sku', $request->product_sku)->first();
        } else {
            $product = Product::find($request->product_id);
        }

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error_code' => 'PRODUCT_NOT_FOUND'
            ], 404);
        }

        $available = $product->stock >= $request->quantity && $product->active;
        $stockStatus = $product->calculateStatus();

        return response()->json([
            'success' => true,
            'available' => $available,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'formatted_price' => $product->formatted_price,
                'stock' => $product->stock,
                'status' => $stockStatus,
                'active' => $product->active
            ],
            'requested_quantity' => $request->quantity,
            'can_purchase' => $available,
            'message' => $available 
                ? 'Product is available for purchase' 
                : 'Product is not available in the requested quantity'
        ]);
    }

    /**
     * Update order status (admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        // Log activity
        activity()
            ->causedBy($request->user())
            ->performedOn($order)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'notes' => $request->notes
            ])
            ->log('Order status updated');

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'order' => $order->load('product', 'user', 'latestPayment')
        ]);
    }

    /**
     * Get order payment status
     */
    public function getPaymentStatus(Request $request, $id)
    {
        $order = Order::with('latestPayment')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => $order->total_price,
            'formatted_amount' => 'KES ' . number_format($order->total_price, 2),
            'payment' => $order->latestPayment ? [
                'id' => $order->latestPayment->id,
                'payment_method' => $order->latestPayment->payment_method,
                'status' => $order->latestPayment->status,
                'transaction_id' => $order->latestPayment->transaction_id,
                'created_at' => $order->latestPayment->created_at,
                'paid_at' => $order->latestPayment->paid_at
            ] : null,
            'next_actions' => $this->getNextActions($order)
        ]);
    }

    /**
     * Get next available actions for an order
     */
    private function getNextActions($order)
    {
        $actions = [];

        if ($order->payment_status === 'pending') {
            $actions[] = [
                'action' => 'initiate_payment',
                'description' => 'Complete payment for this order',
                'endpoints' => [
                    'mpesa' => '/api/payments/mpesa/initiate',
                    'flutterwave' => '/api/payments/flutterwave/initiate'
                ]
            ];
        }

        if ($order->payment_status === 'pending' && $order->latestPayment) {
            $actions[] = [
                'action' => 'verify_payment',
                'description' => 'Check payment status',
                'endpoints' => [
                    'mpesa' => '/api/payments/mpesa/verify',
                    'flutterwave' => '/api/payments/flutterwave/verify'
                ]
            ];
        }

        if ($order->payment_status === 'failed') {
            $actions[] = [
                'action' => 'retry_payment',
                'description' => 'Try payment again',
                'endpoints' => [
                    'mpesa' => '/api/payments/mpesa/initiate',
                    'flutterwave' => '/api/payments/flutterwave/initiate'
                ]
            ];
        }

        return $actions;
    }

    // List orders for logged-in user
    public function index(Request $request)
    {
        $orders = Order::with(['product', 'latestPayment'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
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

    /**
     * Export orders to Excel
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(
            new OrdersExport($startDate, $endDate), 
            $filename
        );
    }

    /**
     * Export orders to PDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $query = Order::with('product', 'user');
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        $orders = $query->orderBy('created_at', 'desc')->get();
        
        // Configure DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Create HTML content
        $html = view('orders.pdf', compact('orders', 'startDate', 'endDate'))->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        
        return $dompdf->stream($filename);
    }

}
