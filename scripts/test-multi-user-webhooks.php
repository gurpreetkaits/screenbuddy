#!/usr/bin/env php
<?php

/**
 * Multi-User Webhook Test Script
 *
 * Tests that webhooks correctly match subscriptions to different users
 * Ensures no cross-contamination (subscriptions going to wrong user)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Support\Facades\Http;

echo "═══════════════════════════════════════════════════════════════\n";
echo "Multi-User Webhook Matching Test\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get webhook secret
$envPath = __DIR__ . '/../.env';
$envContent = file_get_contents($envPath);
preg_match('/POLAR_WEBHOOK_SECRET=(.*)/', $envContent, $matches);
$webhookSecret = $matches[1] ?? '';

if (empty($webhookSecret)) {
    echo "❌ Error: POLAR_WEBHOOK_SECRET not configured\n";
    exit(1);
}

$webhookUrl = 'http://localhost:8000/api/webhooks/polar';

// Helper function to send webhook
function sendWebhook($url, $secret, $payload) {
    $webhookId = 'whk_test_' . uniqid();
    $timestamp = time();

    $secretKey = $secret;
    if (str_starts_with($secret, 'polar_whs_')) {
        $secretKey = substr($secret, 10);
    }
    $decodedSecret = base64_decode($secretKey);

    $signedContent = $webhookId . '.' . $timestamp . '.' . json_encode($payload);
    $signature = base64_encode(hash_hmac('sha256', $signedContent, $decodedSecret, true));

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'webhook-id: ' . $webhookId,
        'webhook-timestamp: ' . $timestamp,
        'webhook-signature: v1,' . $signature,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'response' => $response];
}

// Get test users
$users = User::whereIn('id', [1, 2, 3, 4])->get()->keyBy('id');

if ($users->count() < 2) {
    echo "❌ Error: Need at least 2 users in database for testing\n";
    echo "Current users: " . $users->count() . "\n";
    exit(1);
}

echo "📊 Testing with users:\n";
foreach ($users as $user) {
    echo "  User #{$user->id}: {$user->email}\n";
}
echo "\n";

// Clear existing test subscriptions
echo "🧹 Clearing previous test subscriptions...\n";
User::whereIn('id', $users->pluck('id'))
    ->update([
        'polar_subscription_id' => null,
        'subscription_status' => 'free',
        'subscription_expires_at' => null,
    ]);
echo "✓ Cleared\n\n";

$testResults = [];
$errors = [];

// Test each user
foreach ($users as $userId => $user) {
    echo "══════════════════════════════════════════════════════\n";
    echo "Testing User #{$userId}: {$user->email}\n";
    echo "══════════════════════════════════════════════════════\n";

    $subscriptionId = "sub_test_user{$userId}_" . uniqid();
    $customerId = "cust_test_user{$userId}_" . uniqid();

    // Test 1: subscription.created with external_id
    echo "Test 1: subscription.created with external_id...\n";

    $payload = [
        'type' => 'subscription.created',
        'data' => [
            'id' => $subscriptionId,
            'customer_id' => $customerId,
            'product_id' => 'prod_test',
            'price_id' => 'price_test',
            'status' => 'active',
            'current_period_end' => date('c', strtotime('+1 month')),
            'customer' => [
                'external_id' => (string) $userId,
            ],
            'metadata' => [
                'user_id' => (string) $userId,
            ],
        ],
    ];

    $result = sendWebhook($webhookUrl, $webhookSecret, $payload);

    if ($result['code'] === 200) {
        echo "  ✓ Webhook accepted (HTTP 200)\n";

        // Verify user was updated
        $user->refresh();
        if ($user->polar_subscription_id === $subscriptionId) {
            echo "  ✓ Subscription correctly assigned to user #{$userId}\n";
            $testResults[] = [
                'user' => $userId,
                'test' => 'external_id',
                'status' => 'PASS',
            ];
        } else {
            echo "  ❌ ERROR: Subscription not assigned correctly\n";
            echo "     Expected: {$subscriptionId}\n";
            echo "     Got: " . ($user->polar_subscription_id ?? 'null') . "\n";
            $errors[] = "User #{$userId}: subscription not assigned";
            $testResults[] = [
                'user' => $userId,
                'test' => 'external_id',
                'status' => 'FAIL',
            ];
        }

        // Check no other user got this subscription
        $wrongUsers = User::where('polar_subscription_id', $subscriptionId)
            ->where('id', '!=', $userId)
            ->get();

        if ($wrongUsers->isEmpty()) {
            echo "  ✓ No other users affected\n";
        } else {
            echo "  ❌ ERROR: Subscription also assigned to wrong users:\n";
            foreach ($wrongUsers as $wu) {
                echo "     - User #{$wu->id}: {$wu->email}\n";
                $errors[] = "User #{$wu->id} incorrectly got subscription for user #{$userId}";
            }
        }

        // Verify history was created
        $history = SubscriptionHistory::where('user_id', $userId)
            ->where('polar_subscription_id', $subscriptionId)
            ->first();

        if ($history) {
            echo "  ✓ Subscription history recorded\n";
        } else {
            echo "  ❌ ERROR: No subscription history found\n";
            $errors[] = "User #{$userId}: history not created";
        }

    } else {
        echo "  ❌ ERROR: Webhook rejected (HTTP {$result['code']})\n";
        echo "  Response: {$result['response']}\n";
        $errors[] = "User #{$userId}: webhook rejected";
        $testResults[] = [
            'user' => $userId,
            'test' => 'external_id',
            'status' => 'FAIL',
        ];
    }

    echo "\n";
}

// Final report
echo "═══════════════════════════════════════════════════════════════\n";
echo "Test Results Summary\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$passed = array_filter($testResults, fn($r) => $r['status'] === 'PASS');
$failed = array_filter($testResults, fn($r) => $r['status'] === 'FAIL');

echo "Total Tests: " . count($testResults) . "\n";
echo "Passed: " . count($passed) . " ✓\n";
echo "Failed: " . count($failed) . " ✗\n\n";

if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  ❌ {$error}\n";
    }
    echo "\n";
}

// Verify final state
echo "Final User States:\n";
echo str_repeat("─", 67) . "\n";
foreach ($users as $user) {
    $user->refresh();
    $subId = $user->polar_subscription_id ?? 'none';
    $status = $user->subscription_status;
    echo sprintf("User #%d (%s): %s [%s]\n",
        $user->id,
        $user->email,
        $subId,
        $status
    );
}
echo "\n";

if (empty($errors)) {
    echo "✅ All tests passed! User matching is working correctly.\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Review errors above.\n";
    exit(1);
}
