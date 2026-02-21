<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | M-PESA Configuration
    |--------------------------------------------------------------------------
    |
    | These are M-PESA API credentials for Safaricom M-PESA API.
    | You can get these credentials from Safaricom Developer Portal.
    |
    */

    'mpesa' => [
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'), // sandbox or live
        'callback_url' => env('MPESA_CALLBACK_URL', 'https://yourdomain.com/api/callbacks/mpesa'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Flutterwave Configuration
    |--------------------------------------------------------------------------
    |
    | These are Flutterwave API credentials for payment processing.
    | You can get these credentials from Flutterwave Developer Portal.
    |
    */

    'flutterwave' => [
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'environment' => env('FLUTTERWAVE_ENVIRONMENT', 'sandbox'), // sandbox or live
        'callback_url' => env('FLUTTERWAVE_CALLBACK_URL', env('APP_URL') . '/api/callbacks/flutterwave'),
    ],

];
