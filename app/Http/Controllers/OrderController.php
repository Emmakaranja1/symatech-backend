<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Dompdf\Dompdf;
use Dompdf\Options;

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
                'quantity' => 'required|integer|min:1',
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

        // Validate stock
        if ($product->stock < $validated['quantity']) {
            return response()->json(['message' => 'Insufficient stock'], 400);
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
            'message' => 'Order placed successfully',
            'order_id' => $order->id,
            'product_name' => $product->name,
            'quantity' => $order->quantity,
            'total' => $order->total_price,
            'payment_status' => $order->payment_status,
            'next_step' => 'Proceed to payment to complete your order'
        ], 201);
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
