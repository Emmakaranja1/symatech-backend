<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaService;
use App\Services\FlutterwaveService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $mpesaService;
    protected $flutterwaveService;

    public function __construct(MpesaService $mpesaService, FlutterwaveService $flutterwaveService)
    {
        $this->mpesaService = $mpesaService;
        $this->flutterwaveService = $flutterwaveService;
    }

    /**
     * Initiate M-PESA payment
     */
    public function initiateMpesaPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/', // Kenya format: 254XXXXXXXXX
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            
            // Debug logging
            Log::info('M-PESA Payment attempt', [
                'order_id' => $request->order_id,
                'authenticated_user_id' => $request->user()->id,
                'order_user_id' => $order->user_id,
                'user_email' => $request->user()->email,
                'user_role' => $request->user()->role
            ]);
            
            // Check if order belongs to authenticated user
            if ($order->user_id !== $request->user()->id) {
                Log::warning('Unauthorized M-PESA payment attempt', [
                    'order_id' => $request->order_id,
                    'authenticated_user_id' => $request->user()->id,
                    'order_user_id' => $order->user_id
                ]);
                return response()->json([
                    'message' => 'Unauthorized - Order does not belong to this user',
                    'debug' => [
                        'order_user_id' => $order->user_id,
                        'your_user_id' => $request->user()->id
                    ]
                ], 403);
            }

            // Check if order is already paid
            if ($order->payment_status === 'paid') {
                return response()->json(['message' => 'Order already paid'], 400);
            }

            $response = $this->mpesaService->initiateStkPush(
                $request->phone_number,
                $order->total_price,
                $order->id,
                "Payment for Order #{$order->id}"
            );

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => Payment::METHOD_MPESA,
                'amount' => $order->total_price,
                'status' => Payment::STATUS_PENDING,
                'transaction_id' => $response['CheckoutRequestID'] ?? null,
                'phone_number' => $request->phone_number,
                'response_data' => json_encode($response)
            ]);

            // Log activity
            activity()
                ->causedBy($request->user())
                ->performedOn($payment)
                ->withProperties([
                    'order_id' => $order->id,
                    'amount' => $order->total_price,
                    'payment_method' => 'mpesa'
                ])
                ->log('M-PESA payment initiated');

            return response()->json([
                'message' => 'M-PESA payment initiated successfully',
                'payment_id' => $payment->id,
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'customer_message' => 'Please check your phone to complete payment'
            ], 200);

        } catch (\Exception $e) {
            Log::error('M-PESA Payment Initiation Error', [
                'error' => $e->getMessage(),
                'order_id' => $request->order_id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'message' => 'Failed to initiate M-PESA payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify M-PESA payment
     */
    public function verifyMpesaPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'checkout_request_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $response = $this->mpesaService->verifyPayment($request->checkout_request_id);
            
            $resultCode = $response['ResultCode'] ?? null;
            $payment = Payment::where('transaction_id', $request->checkout_request_id)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            if ($resultCode === 0) {
                // Payment successful
                $payment->markAsCompleted();
                $payment->order->markAsPaid();

                // Log activity
                activity()
                    ->causedBy($request->user())
                    ->performedOn($payment)
                    ->withProperties([
                        'order_id' => $payment->order_id,
                        'amount' => $payment->amount,
                        'payment_method' => 'mpesa'
                    ])
                    ->log('M-PESA payment completed successfully');

                return response()->json([
                    'message' => 'Payment completed successfully',
                    'payment_status' => 'completed',
                    'order_status' => 'paid',
                    'response' => $response
                ], 200);
            } else {
                // Payment failed
                $payment->markAsFailed();
                $payment->order->markPaymentFailed();

                return response()->json([
                    'message' => 'Payment failed',
                    'payment_status' => 'failed',
                    'order_status' => 'failed',
                    'response' => $response
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('M-PESA Payment Verification Error', [
                'error' => $e->getMessage(),
                'checkout_request_id' => $request->checkout_request_id
            ]);

            return response()->json([
                'message' => 'Failed to verify M-PESA payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate Flutterwave payment
     */
    public function initiateFlutterwavePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);
            
            // Check if order belongs to authenticated user
            if ($order->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check if order is already paid
            if ($order->payment_status === 'paid') {
                return response()->json(['message' => 'Order already paid'], 400);
            }

            $response = $this->flutterwaveService->createPaymentLink(
                $order->total_price,
                $order->id,
                $request->email,
                $request->name
            );

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => Payment::METHOD_FLUTTERWAVE,
                'amount' => $order->total_price,
                'status' => Payment::STATUS_PENDING,
                'transaction_id' => $response['data']['tx_ref'] ?? null,
                'email' => $request->email,
                'response_data' => json_encode($response)
            ]);

            // Log activity
            activity()
                ->causedBy($request->user())
                ->performedOn($payment)
                ->withProperties([
                    'order_id' => $order->id,
                    'amount' => $order->total_price,
                    'payment_method' => 'flutterwave'
                ])
                ->log('Flutterwave payment initiated');

            return response()->json([
                'message' => 'Flutterwave payment initiated successfully',
                'payment_id' => $payment->id,
                'payment_link' => $response['data']['link'] ?? null,
                'transaction_reference' => $response['data']['tx_ref'] ?? null
            ], 200);

        } catch (\Exception $e) {
            Log::error('Flutterwave Payment Initiation Error', [
                'error' => $e->getMessage(),
                'order_id' => $request->order_id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'message' => 'Failed to initiate Flutterwave payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Flutterwave payment
     */
    public function verifyFlutterwavePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $response = $this->flutterwaveService->verifyPayment($request->transaction_id);
            
            $payment = Payment::where('transaction_id', $response['data']['tx_ref'] ?? null)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            if ($response['data']['status'] === 'successful') {
                // Payment successful
                $payment->markAsCompleted();
                $payment->order->markAsPaid();

                // Log activity
                activity()
                    ->causedBy($request->user())
                    ->performedOn($payment)
                    ->withProperties([
                        'order_id' => $payment->order_id,
                        'amount' => $payment->amount,
                        'payment_method' => 'flutterwave'
                    ])
                    ->log('Flutterwave payment completed successfully');

                return response()->json([
                    'message' => 'Payment completed successfully',
                    'payment_status' => 'completed',
                    'order_status' => 'paid',
                    'response' => $response
                ], 200);
            } else {
                // Payment failed
                $payment->markAsFailed();
                $payment->order->markPaymentFailed();

                return response()->json([
                    'message' => 'Payment failed',
                    'payment_status' => 'failed',
                    'order_status' => 'failed',
                    'response' => $response
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Flutterwave Payment Verification Error', [
                'error' => $e->getMessage(),
                'transaction_id' => $request->transaction_id
            ]);

            return response()->json([
                'message' => 'Failed to verify Flutterwave payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user payments
     */
    public function getUserPayments(Request $request)
    {
        $payments = Payment::with('order')
            ->whereHas('order', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($payments, 200);
    }

    /**
     * Get admin payments
     */
    public function getAdminPayments(Request $request)
    {
        $query = Payment::with(['order.user', 'order.product']);

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($payments, 200);
    }

    /**
     * Handle M-PESA callback
     */
    public function handleMpesaCallback(Request $request)
    {
        try {
            $callbackData = $request->all();
            
            Log::info('M-PESA Callback Received', ['data' => $callbackData]);

            $processedData = $this->mpesaService->processCallback($callbackData);
            
            if ($processedData['success']) {
                $payment = Payment::where('transaction_id', $processedData['checkout_request_id'])->first();
                
                if ($payment) {
                    $payment->markAsCompleted();
                    $payment->order->markAsPaid();
                    
                    Log::info('M-PESA Payment completed via callback', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id
                    ]);
                }
            } else {
                $payment = Payment::where('transaction_id', $processedData['checkout_request_id'])->first();
                
                if ($payment) {
                    $payment->markAsFailed();
                    $payment->order->markPaymentFailed();
                    
                    Log::info('M-PESA Payment failed via callback', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'error' => $processedData['result_desc']
                    ]);
                }
            }

            return response()->json(['message' => 'Callback processed successfully'], 200);

        } catch (\Exception $e) {
            Log::error('M-PESA Callback Error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['message' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Handle Flutterwave callback
     */
    public function handleFlutterwaveCallback(Request $request)
    {
        try {
            $signature = $request->header('verif-hash');
            $payload = $request->getContent();
            
            // Verify webhook signature
            if (!$this->flutterwaveService->validateWebhookSignature($signature, $payload)) {
                Log::warning('Invalid Flutterwave webhook signature');
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $callbackData = $request->all();
            
            Log::info('Flutterwave Callback Received', ['data' => $callbackData]);

            $processedData = $this->flutterwaveService->processCallback($callbackData);
            
            if ($processedData['success']) {
                $payment = Payment::where('transaction_id', $processedData['tx_ref'])->first();
                
                if ($payment) {
                    $payment->markAsCompleted();
                    $payment->order->markAsPaid();
                    
                    Log::info('Flutterwave Payment completed via callback', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id
                    ]);
                }
            } else {
                $payment = Payment::where('transaction_id', $processedData['tx_ref'])->first();
                
                if ($payment) {
                    $payment->markAsFailed();
                    $payment->order->markPaymentFailed();
                    
                    Log::info('Flutterwave Payment failed via callback', [
                        'payment_id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'error' => $processedData['error']
                    ]);
                }
            }

            return response()->json(['message' => 'Callback processed successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Flutterwave Callback Error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['message' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Process refund (Flutterwave only)
     */
    public function processRefund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:payments,id',
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $payment = Payment::findOrFail($request->payment_id);
            
            if ($payment->payment_method !== Payment::METHOD_FLUTTERWAVE) {
                return response()->json(['message' => 'Refund only available for Flutterwave payments'], 400);
            }

            if (!$payment->isCompleted()) {
                return response()->json(['message' => 'Only completed payments can be refunded'], 400);
            }

            $response = $this->flutterwaveService->processRefund(
                $payment->transaction_id,
                $request->amount ?? $payment->amount
            );

            // Update payment status
            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
            ]);

            // Log activity
            activity()
                ->causedBy($request->user())
                ->performedOn($payment)
                ->withProperties([
                    'order_id' => $payment->order_id,
                    'amount' => $request->amount ?? $payment->amount,
                    'reason' => $request->reason
                ])
                ->log('Payment refunded');

            return response()->json([
                'message' => 'Refund processed successfully',
                'refund_data' => $response
            ], 200);

        } catch (\Exception $e) {
            Log::error('Refund Processing Error', [
                'error' => $e->getMessage(),
                'payment_id' => $request->payment_id
            ]);

            return response()->json([
                'message' => 'Failed to process refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
