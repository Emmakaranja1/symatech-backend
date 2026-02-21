<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlutterwaveService
{
    private $secretKey;
    private $publicKey;
    private $encryptionKey;
    private $baseUrl;
    private $callbackUrl;

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key');
        $this->publicKey = config('services.flutterwave.public_key');
        $this->encryptionKey = config('services.flutterwave.encryption_key');
        $this->baseUrl = config('services.flutterwave.environment') === 'live'
            ? 'https://api.flutterwave.com/v3'
            : 'https://ravesandboxapi.flutterwave.com/v3';
        $this->callbackUrl = config('services.flutterwave.callback_url');
    }

    /**
     * Create payment link
     */
    public function createPaymentLink($amount, $orderId, $email, $name = null)
    {
        try {
            $payload = [
                'tx_ref' => 'ORDER-' . $orderId . '-' . time(),
                'amount' => $amount,
                'currency' => 'KES',
                'email' => $email,
                'name' => $name ?? $email,
                'redirect_url' => $this->callbackUrl,
                'payment_options' => 'card,banktransfer,mpesa',
                'customizations' => [
                    'title' => 'Order #' . $orderId,
                    'description' => 'Payment for Order #' . $orderId,
                ],
                'meta' => [
                    'order_id' => $orderId,
                    'source' => 'api'
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/payments', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Flutterwave payment link creation failed', [
                'response' => $response->body(),
                'payload' => $payload
            ]);

            throw new \Exception('Flutterwave payment link creation failed: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('Flutterwave Service Error', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'order_id' => $orderId,
                'email' => $email
            ]);
            
            throw $e;
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment($transactionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/transactions/' . $transactionId . '/verify');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Flutterwave payment verification failed', [
                'response' => $response->body(),
                'transaction_id' => $transactionId
            ]);

            throw new \Exception('Flutterwave payment verification failed');
            
        } catch (\Exception $e) {
            Log::error('Flutterwave Verification Error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);
            
            throw $e;
        }
    }

    /**
     * Process refund
     */
    public function processRefund($transactionId, $amount = null)
    {
        try {
            $payload = [
                'id' => $transactionId,
                'amount' => $amount,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/transactions/' . $transactionId . '/refund', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Flutterwave refund failed', [
                'response' => $response->body(),
                'transaction_id' => $transactionId,
                'amount' => $amount
            ]);

            throw new \Exception('Flutterwave refund failed');
            
        } catch (\Exception $e) {
            Log::error('Flutterwave Refund Error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'amount' => $amount
            ]);
            
            throw $e;
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction($transactionId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/transactions/' . $transactionId);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to get transaction details');
            
        } catch (\Exception $e) {
            Log::error('Flutterwave Transaction Error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);
            
            throw $e;
        }
    }

    /**
     * Process webhook callback
     */
    public function processCallback($callbackData)
    {
        try {
            $event = $callbackData['event'] ?? null;
            $data = $callbackData['data'] ?? null;

            if ($event === 'charge.completed' && $data) {
                return [
                    'success' => true,
                    'transaction_id' => $data['id'] ?? null,
                    'tx_ref' => $data['tx_ref'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'currency' => $data['currency'] ?? null,
                    'status' => $data['status'] ?? null,
                    'customer' => $data['customer'] ?? [],
                    'payment_type' => $data['payment_type'] ?? null,
                ];
            } elseif ($event === 'charge.failed' && $data) {
                return [
                    'success' => false,
                    'transaction_id' => $data['id'] ?? null,
                    'tx_ref' => $data['tx_ref'] ?? null,
                    'error' => $data['failure_reason'] ?? 'Payment failed'
                ];
            }

            return [
                'success' => false,
                'error' => 'Unknown event type'
            ];
            
        } catch (\Exception $e) {
            Log::error('Flutterwave Callback Error', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature($signature, $payload)
    {
        try {
            $computedSignature = hash_hmac('sha512', $payload, $this->secretKey);
            return hash_equals($signature, $computedSignature);
        } catch (\Exception $e) {
            Log::error('Flutterwave Webhook Signature Validation Error', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
