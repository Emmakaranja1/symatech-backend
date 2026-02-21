<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{
    private $consumerKey;
    private $consumerSecret;
    private $passkey;
    private $shortcode;
    private $baseUrl;
    private $callbackUrl;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->passkey = config('services.mpesa.passkey') ?: 'bfb279f9b9d4f8c2b8a5b7c5f3e1c2f3B'; // Fallback to standard test passkey
        $this->shortcode = config('services.mpesa.shortcode') ?: '174379'; // Fallback to standard test shortcode
        $this->baseUrl = config('services.mpesa.environment') === 'live' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
        $this->callbackUrl = config('services.mpesa.callback_url');
    }

    /**
     * Get OAuth access token
     */
    public function getAccessToken()
    {
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        
        Log::info('M-PESA OAuth Request', [
            'url' => $url,
            'consumer_key' => $this->consumerKey ? 'SET' : 'NOT SET',
            'consumer_secret' => $this->consumerSecret ? 'SET' : 'NOT SET'
        ]);
        
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get($url);

        Log::info('M-PESA OAuth Response', [
            'status' => $response->status(),
            'body' => $response->body(),
            'successful' => $response->successful()
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        Log::error('M-PESA OAuth failed', ['response' => $response->body()]);
        throw new \Exception('Failed to get M-PESA access token');
    }

    /**
     * Initiate STK Push payment
     */
    public function initiateStkPush($phoneNumber, $amount, $orderId, $orderDescription)
    {
        try {
            $token = $this->getAccessToken();
            
            $timestamp = date('YmdHis');
            // Try different encoding methods
            $password1 = base64_encode($this->shortcode . $this->passkey . $timestamp);
            $password2 = base64_encode($this->shortcode . $this->passkey . $timestamp . ''); // Add empty string
            
            Log::info('M-PESA Password Generation', [
                'shortcode' => $this->shortcode,
                'passkey' => $this->passkey,
                'timestamp' => $timestamp,
                'password1' => $password1,
                'password2' => $password2
            ]);
            
            $password = $password2; // Use second method
            
            $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
            
            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) $amount,
                'PartyA' => $phoneNumber,
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => $this->callbackUrl,
                'AccountReference' => 'ORDER-' . $orderId,
                'TransactionDesc' => $orderDescription,
            ];

            // Debug logging
            Log::info('M-PESA STK Push Request', [
                'url' => $url,
                'shortcode' => $this->shortcode,
                'passkey' => $this->passkey,
                'timestamp' => $timestamp,
                'password' => $password,
                'callback_url' => $this->callbackUrl,
                'payload' => $payload
            ]);

            $response = Http::withToken($token)
                ->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('M-PESA STK Push failed', [
                'response' => $response->body(),
                'payload' => $payload
            ]);

            throw new \Exception('M-PESA STK Push failed: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('M-PESA Service Error', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'amount' => $amount,
                'order_id' => $orderId
            ]);
            
            throw $e;
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment($checkoutRequestId)
    {
        try {
            $token = $this->getAccessToken();
            
            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            
            $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
            
            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ];

            $response = Http::withToken($token)
                ->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('M-PESA verification failed', [
                'response' => $response->body(),
                'checkout_request_id' => $checkoutRequestId
            ]);

            throw new \Exception('M-PESA verification failed');
            
        } catch (\Exception $e) {
            Log::error('M-PESA Verification Error', [
                'error' => $e->getMessage(),
                'checkout_request_id' => $checkoutRequestId
            ]);
            
            throw $e;
        }
    }

    /**
     * Process callback response
     */
    public function processCallback($callbackData)
    {
        try {
            $resultCode = $callbackData['Body']['stkCallback']['ResultCode'] ?? null;
            $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'] ?? null;
            
            if ($resultCode === 0) {
                // Payment successful
                $metadata = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
                $mpesaReceipt = null;
                $phoneNumber = null;
                
                foreach ($metadata as $item) {
                    if ($item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaReceipt = $item['Value'];
                    }
                    if ($item['Name'] === 'PhoneNumber') {
                        $phoneNumber = $item['Value'];
                    }
                }

                return [
                    'success' => true,
                    'checkout_request_id' => $checkoutRequestId,
                    'mpesa_receipt' => $mpesaReceipt,
                    'phone_number' => $phoneNumber,
                    'result_code' => $resultCode,
                    'result_desc' => 'Success'
                ];
            } else {
                // Payment failed
                return [
                    'success' => false,
                    'checkout_request_id' => $checkoutRequestId,
                    'result_code' => $resultCode,
                    'result_desc' => $callbackData['Body']['stkCallback']['ResultDesc'] ?? 'Unknown error'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('M-PESA Callback Error', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
