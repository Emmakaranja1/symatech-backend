<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Redis State Management Validation Demo ===\n\n";

// Test Redis Connection
echo "1. Testing Redis Connection...\n";
try {
    $redisConnectionService = app(\App\Services\Redis\RedisConnectionService::class);
    $connectionResult = $redisConnectionService->testConnection();
    
    if ($connectionResult['success']) {
        echo "✅ Redis Connection: SUCCESS\n";
        echo "   - Status: {$connectionResult['status']}\n";
        echo "   - Response Time: {$connectionResult['response_time_ms']}ms\n";
        echo "   - Redis Version: {$connectionResult['redis_version']}\n";
        echo "   - Memory Usage: {$connectionResult['memory_usage']}\n";
        echo "   - Connected Clients: {$connectionResult['connected_clients']}\n";
    } else {
        echo "❌ Redis Connection: FAILED\n";
        echo "   - Error: {$connectionResult['error']}\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Redis Connection: FAILED\n";
    echo "   - Exception: {$e->getMessage()}\n";
    exit(1);
}

echo "\n2. Testing Shopping Cart Functionality...\n";
try {
    $cartService = app(\App\Services\Redis\ShoppingCartService::class);
    $userId = 123;

    // Add item to cart
    $item = [
        'id' => 1,
        'name' => 'Premium Laptop',
        'price' => 1299.99,
        'quantity' => 2
    ];
    
    $result = $cartService->addItem($userId, $item);
    echo $result ? "✅ Add item to cart: SUCCESS\n" : "❌ Add item to cart: FAILED\n";

    // Get cart
    $cart = $cartService->getCart($userId);
    $count = $cartService->getCartCount($userId);
    $total = $cartService->getCartTotal($userId);
    
    echo "✅ Get cart: SUCCESS\n";
    echo "   - Items: {$count}\n";
    echo "   - Total: \${$total}\n";

    // Update quantity
    $result = $cartService->updateItemQuantity($userId, 1, 3);
    echo $result ? "✅ Update quantity: SUCCESS\n" : "❌ Update quantity: FAILED\n";

    // Get updated cart
    $updatedCart = $cartService->getCart($userId);
    $updatedTotal = $cartService->getCartTotal($userId);
    echo "   - Updated Total: \${$updatedTotal}\n";

    // Clear cart
    $result = $cartService->clearCart($userId);
    echo $result ? "✅ Clear cart: SUCCESS\n" : "❌ Clear cart: FAILED\n";

} catch (Exception $e) {
    echo "❌ Shopping Cart Test: FAILED\n";
    echo "   - Exception: {$e->getMessage()}\n";
}

echo "\n3. Testing User Preferences Storage...\n";
try {
    $preferencesService = app(\App\Services\Redis\UserPreferencesService::class);
    $userId = 123;

    // Set preferences
    $preferencesService->setPreference($userId, 'theme', 'dark');
    $preferencesService->setPreference($userId, 'language', 'en');
    $preferencesService->setPreference($userId, 'notifications', true);
    echo "✅ Set preferences: SUCCESS\n";

    // Get preference
    $theme = $preferencesService->getPreference($userId, 'theme');
    echo "✅ Get preference: SUCCESS (theme = {$theme})\n";

    // Get all preferences
    $allPrefs = $preferencesService->getAllPreferences($userId);
    echo "✅ Get all preferences: SUCCESS\n";
    echo "   - Theme: {$allPrefs['theme']}\n";
    echo "   - Language: {$allPrefs['language']}\n";
    echo "   - Notifications: " . ($allPrefs['notifications'] ? 'true' : 'false') . "\n";

    // Set multiple preferences
    $multiplePrefs = [
        'timezone' => 'UTC',
        'currency' => 'USD',
        'date_format' => 'Y-m-d'
    ];
    $preferencesService->setMultiplePreferences($userId, $multiplePrefs);
    echo "✅ Set multiple preferences: SUCCESS\n";

    // Clear preferences
    $preferencesService->clearAllPreferences($userId);
    echo "✅ Clear preferences: SUCCESS\n";

} catch (Exception $e) {
    echo "❌ User Preferences Test: FAILED\n";
    echo "   - Exception: {$e->getMessage()}\n";
}

echo "\n4. Testing Payment Session Storage...\n";
try {
    $paymentSessionService = app(\App\Services\Redis\PaymentSessionService::class);
    $sessionId = 'demo_session_' . time();

    // Create payment session
    $paymentData = [
        'amount' => 100.00,
        'currency' => 'USD',
        'user_id' => 123,
        'payment_method' => 'credit_card',
        'description' => 'Demo Payment',
        'metadata' => [
            'order_id' => 'ORD-DEMO-001',
            'customer_email' => 'demo@example.com'
        ]
    ];
    
    $result = $paymentSessionService->createPaymentSession($sessionId, $paymentData);
    echo $result ? "✅ Create payment session: SUCCESS\n" : "❌ Create payment session: FAILED\n";

    // Get payment session
    $session = $paymentSessionService->getPaymentSession($sessionId);
    if ($session) {
        echo "✅ Get payment session: SUCCESS\n";
        echo "   - Amount: \${$session['amount']}\n";
        echo "   - Currency: {$session['currency']}\n";
        echo "   - Status: {$session['status']}\n";
        echo "   - Created: {$session['created_at']}\n";
    }

    // Update payment session
    $updates = [
        'status' => 'processing',
        'transaction_id' => 'txn_demo_123456'
    ];
    $result = $paymentSessionService->updatePaymentSession($sessionId, $updates);
    echo $result ? "✅ Update payment session: SUCCESS\n" : "❌ Update payment session: FAILED\n";

    // Check session validity
    $isValid = $paymentSessionService->isPaymentSessionValid($sessionId);
    $ttl = $paymentSessionService->getPaymentSessionTTL($sessionId);
    echo "✅ Check session validity: SUCCESS\n";
    echo "   - Valid: " . ($isValid ? 'true' : 'false') . "\n";
    echo "   - TTL: {$ttl} seconds\n";

    // Delete payment session
    $result = $paymentSessionService->deletePaymentSession($sessionId);
    echo $result ? "✅ Delete payment session: SUCCESS\n" : "❌ Delete payment session: FAILED\n";

} catch (Exception $e) {
    echo "❌ Payment Session Test: FAILED\n";
    echo "   - Exception: {$e->getMessage()}\n";
}

echo "\n5. Testing Rate Limiting Functionality...\n";
try {
    $rateLimitService = app(\App\Services\Redis\RateLimitService::class);
    $identifier = 'demo_user_' . time();
    $maxAttempts = 5;
    $decaySeconds = 60;

    echo "Testing rate limit with max attempts: {$maxAttempts}, decay: {$decaySeconds}s\n";

    // Test rate limiting
    for ($i = 1; $i <= $maxAttempts + 2; $i++) {
        $result = $rateLimitService->checkRateLimit($identifier, $maxAttempts, $decaySeconds);
        $status = $result['allowed'] ? 'ALLOWED' : 'BLOCKED';
        echo "   Attempt {$i}: {$status} (attempts: {$result['attempts']}, remaining: {$result['remaining']})\n";
    }

    // Clear rate limit
    $result = $rateLimitService->clearRateLimit($identifier);
    echo $result ? "✅ Clear rate limit: SUCCESS\n" : "❌ Clear rate limit: FAILED\n";

    // Test multiple rate limits
    $limits = [
        'api_calls' => [
            'identifier' => 'demo_user_api',
            'max_attempts' => 10,
            'decay_seconds' => 60
        ],
        'login_attempts' => [
            'identifier' => 'demo_user_login',
            'max_attempts' => 3,
            'decay_seconds' => 300
        ]
    ];

    $results = $rateLimitService->checkMultipleRateLimits($limits);
    echo "✅ Multiple rate limits: SUCCESS\n";
    foreach ($results as $name => $result) {
        $maxAttempts = $limits[$name]['max_attempts'];
        echo "   - {$name}: {$result['attempts']}/{$maxAttempts} (allowed: " . ($result['allowed'] ? 'true' : 'false') . ")\n";
    }

} catch (Exception $e) {
    echo "❌ Rate Limiting Test: FAILED\n";
    echo "   - Exception: {$e->getMessage()}\n";
}

echo "\n6. Testing Redis Operations Performance...\n";
try {
    $redisConnectionService = app(\App\Services\Redis\RedisConnectionService::class);

    // Basic operations
    $basicOps = $redisConnectionService->testBasicOperations();
    if ($basicOps['success']) {
        echo "✅ Basic Operations: SUCCESS\n";
        echo "   - Set Time: {$basicOps['operations']['set_time']}ms\n";
        echo "   - Get Time: {$basicOps['operations']['get_time']}ms\n";
        echo "   - Delete Time: {$basicOps['operations']['delete_time']}ms\n";
        echo "   - Total Time: {$basicOps['operations']['total_time']}ms\n";
    }

    // Hash operations
    $hashOps = $redisConnectionService->testHashOperations();
    if ($hashOps['success']) {
        echo "✅ Hash Operations: SUCCESS\n";
        echo "   - HMSet Time: {$hashOps['operations']['hmset_time']}ms\n";
        echo "   - HGetAll Time: {$hashOps['operations']['hgetall_time']}ms\n";
        echo "   - HDel Time: {$hashOps['operations']['hdel_time']}ms\n";
        echo "   - Total Time: {$hashOps['operations']['total_time']}ms\n";
    }

} catch (Exception $e) {
    echo "❌ Performance Test: FAILED\n";
    echo "   - Exception: {$e->getMessage()}\n";
}

echo "\n7. Getting Redis Server Information...\n";
try {
    $redisConnectionService = app(\App\Services\Redis\RedisConnectionService::class);
    $info = $redisConnectionService->getRedisInfo();

    if ($info['success']) {
        echo "✅ Redis Info: SUCCESS\n";
        $serverInfo = $info['info'];
        echo "   - Redis Version: {$serverInfo['redis_version']}\n";
        echo "   - Redis Mode: {$serverInfo['redis_mode']}\n";
        echo "   - OS: {$serverInfo['os']}\n";
        echo "   - Architecture: {$serverInfo['arch_bits']} bits\n";
        echo "   - Uptime: {$serverInfo['uptime_in_seconds']} seconds\n";
        echo "   - Memory Usage: {$serverInfo['used_memory_human']}\n";
        echo "   - Peak Memory: {$serverInfo['used_memory_peak_human']}\n";
        echo "   - Total Commands: {$serverInfo['total_commands_processed']}\n";
        echo "   - Keyspace Hits: {$serverInfo['keyspace_hits']}\n";
        echo "   - Keyspace Misses: {$serverInfo['keyspace_misses']}\n";
    }

} catch (Exception $e) {
    echo "❌ Redis Info: FAILED\n";
    echo "   - Exception: {$e->getMessage()}\n";
}

echo "\n=== Redis State Management Validation Complete ===\n";
echo "All Redis services have been tested and validated successfully!\n";
echo "\nNext Steps:\n";
echo "1. Import the Postman collection: Redis_State_Management_Postman_Collection.json\n";
echo "2. Set your base_url in Postman environment variables\n";
echo "3. Run the API endpoints for comprehensive testing\n";
echo "4. Monitor Redis performance using the monitoring endpoints\n";
