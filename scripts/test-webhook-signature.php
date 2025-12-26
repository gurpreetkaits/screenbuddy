#!/usr/bin/env php
<?php

/**
 * Test Polar Webhook Signature Verification
 *
 * This script tests if our webhook signature verification works correctly.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$webhookSecret = config('services.polar.webhook_secret');

echo "Testing Webhook Signature Verification\n";
echo "======================================\n\n";

echo "Webhook Secret: " . substr($webhookSecret, 0, 20) . "...\n\n";

// Test data
$webhookId = "test_webhook_" . uniqid();
$webhookTimestamp = (string) time();
$payload = json_encode([
    'type' => 'subscription.created',
    'data' => [
        'id' => 'sub_test_123',
        'customer_id' => 'cust_test_456',
        'status' => 'active',
    ],
]);

echo "Test Payload:\n";
echo $payload . "\n\n";

// Extract secret key
$secretKey = $webhookSecret;
if (str_starts_with($webhookSecret, 'polar_whs_')) {
    $secretKey = substr($webhookSecret, 10); // Remove 'polar_whs_' prefix
}

echo "Secret Key (base64): " . substr($secretKey, 0, 20) . "...\n";

// Decode the base64 secret
$decodedSecret = base64_decode($secretKey);
echo "Decoded Secret Length: " . strlen($decodedSecret) . " bytes\n\n";

// Construct signed content (same as Polar)
$signedContent = $webhookId . '.' . $webhookTimestamp . '.' . $payload;

echo "Signed Content:\n";
echo "  webhook_id: $webhookId\n";
echo "  timestamp: $webhookTimestamp\n";
echo "  payload length: " . strlen($payload) . "\n\n";

// Compute signature
$expectedSignature = base64_encode(hash_hmac('sha256', $signedContent, $decodedSecret, true));

echo "Generated Signature: v1,$expectedSignature\n\n";

// Now test verification
echo "Testing Verification:\n";
echo "====================\n\n";

$testSignature = "v1,$expectedSignature";

// Simulate what our controller does
$signatures = explode(' ', $testSignature);

foreach ($signatures as $versionedSig) {
    $parts = explode(',', $versionedSig, 2);
    if (count($parts) !== 2) {
        echo "❌ Invalid signature format\n";
        continue;
    }

    [$version, $signature] = $parts;

    if ($version !== 'v1') {
        echo "❌ Wrong version: $version\n";
        continue;
    }

    $computedSignature = base64_encode(hash_hmac('sha256', $signedContent, $decodedSecret, true));

    if (hash_equals($computedSignature, $signature)) {
        echo "✅ Signature verification PASSED!\n";
        echo "   Expected: $computedSignature\n";
        echo "   Received: $signature\n";
        echo "   Match: YES\n\n";
    } else {
        echo "❌ Signature verification FAILED!\n";
        echo "   Expected: $computedSignature\n";
        echo "   Received: $signature\n";
        echo "   Match: NO\n\n";
    }
}

// Now test sending to actual endpoint
echo "\nTesting Actual Endpoint:\n";
echo "========================\n\n";

$url = 'http://localhost:8000/api/webhooks/polar';

$headers = [
    'Content-Type: application/json',
    "webhook-id: $webhookId",
    "webhook-timestamp: $webhookTimestamp",
    "webhook-signature: v1,$expectedSignature",
];

echo "Sending webhook to: $url\n";
echo "Headers:\n";
foreach ($headers as $header) {
    echo "  $header\n";
}
echo "\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response Body: $response\n\n";

if ($httpCode === 200) {
    echo "✅ Webhook endpoint accepted the request!\n";
} else {
    echo "❌ Webhook endpoint rejected the request\n";
    echo "   This suggests signature verification is failing\n";
}
