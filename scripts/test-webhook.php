#!/usr/bin/env php
<?php

/**
 * Polar Webhook Test Script
 *
 * This script generates a valid webhook signature and sends a test webhook
 * to your local development server. This is useful for testing the webhook
 * endpoint without needing to configure ngrok or wait for real Polar events.
 *
 * Usage:
 *   php scripts/test-webhook.php
 *   php scripts/test-webhook.php --event=subscription.canceled
 *   php scripts/test-webhook.php --url=https://yourdomain.com/api/webhooks/polar
 */

// Parse command line arguments
$options = getopt('', ['event:', 'url:', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
Polar Webhook Test Script

Usage:
  php scripts/test-webhook.php [options]

Options:
  --event=TYPE    Event type to send (default: subscription.created)
                  Available: customer.created, subscription.created,
                            subscription.active, subscription.canceled,
                            subscription.revoked, subscription.updated
  --url=URL       Webhook URL (default: http://localhost:8000/api/webhooks/polar)
  --help          Show this help message

Examples:
  php scripts/test-webhook.php
  php scripts/test-webhook.php --event=subscription.canceled
  php scripts/test-webhook.php --url=https://example.com/api/webhooks/polar

HELP;
    exit(0);
}

// Configuration
$webhookUrl = $options['url'] ?? 'http://localhost:8000/api/webhooks/polar';
$eventType = $options['event'] ?? 'subscription.created';

// Load .env file to get webhook secret
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    echo "❌ Error: .env file not found at {$envPath}\n";
    echo "Please copy .env.example to .env and configure POLAR_WEBHOOK_SECRET\n";
    exit(1);
}

$envContent = file_get_contents($envPath);
preg_match('/POLAR_WEBHOOK_SECRET=(.*)/', $envContent, $matches);
$webhookSecret = $matches[1] ?? '';

if (empty($webhookSecret) || $webhookSecret === 'whsec_xxxxxxxxxxxxx') {
    echo "❌ Error: POLAR_WEBHOOK_SECRET not configured in .env\n";
    echo "For testing, you can use any value, for example:\n";
    echo "POLAR_WEBHOOK_SECRET=polar_whs_" . base64_encode('test_secret_key_12345') . "\n";
    exit(1);
}

// Sample payloads for different event types
$payloads = [
    'customer.created' => [
        'type' => 'customer.created',
        'data' => [
            'id' => 'cust_test_' . uniqid(),
            'external_id' => '1', // User ID 1
            'email' => 'test@example.com',
            'name' => 'Test User',
        ],
    ],
    'subscription.created' => [
        'type' => 'subscription.created',
        'data' => [
            'id' => 'sub_test_' . uniqid(),
            'customer_id' => 'cust_test_123',
            'product_id' => 'prod_test_monthly',
            'price_id' => 'price_test_700',
            'status' => 'active',
            'amount' => 700,
            'recurring_interval' => 'month',
            'current_period_end' => date('c', strtotime('+1 month')),
            'customer' => [
                'external_id' => '1', // User ID
            ],
        ],
    ],
    'subscription.active' => [
        'type' => 'subscription.active',
        'data' => [
            'id' => 'sub_test_' . uniqid(),
            'customer_id' => 'cust_test_123',
            'status' => 'active',
            'current_period_end' => date('c', strtotime('+1 month')),
        ],
    ],
    'subscription.updated' => [
        'type' => 'subscription.updated',
        'data' => [
            'id' => 'sub_test_' . uniqid(),
            'status' => 'active',
            'product_id' => 'prod_test_yearly',
            'price_id' => 'price_test_8000',
            'current_period_end' => date('c', strtotime('+1 year')),
        ],
    ],
    'subscription.canceled' => [
        'type' => 'subscription.canceled',
        'data' => [
            'id' => 'sub_test_' . uniqid(),
        ],
    ],
    'subscription.revoked' => [
        'type' => 'subscription.revoked',
        'data' => [
            'id' => 'sub_test_' . uniqid(),
        ],
    ],
];

if (!isset($payloads[$eventType])) {
    echo "❌ Error: Unknown event type '{$eventType}'\n";
    echo "Available types: " . implode(', ', array_keys($payloads)) . "\n";
    exit(1);
}

$payload = $payloads[$eventType];
$payloadJson = json_encode($payload, JSON_PRETTY_PRINT);

// Generate Standard Webhooks signature
$webhookId = 'whk_test_' . uniqid();
$timestamp = time();

// Extract secret key from Polar format
$secretKey = $webhookSecret;
if (str_starts_with($webhookSecret, 'polar_whs_')) {
    $secretKey = substr($webhookSecret, 10); // Remove 'polar_whs_' prefix
}

// Decode base64 secret
$decodedSecret = base64_decode($secretKey);

// Construct signed content: {id}.{timestamp}.{payload}
$signedContent = $webhookId . '.' . $timestamp . '.' . json_encode($payload);

// Generate HMAC-SHA256 signature
$signature = base64_encode(hash_hmac('sha256', $signedContent, $decodedSecret, true));

// Prepare headers
$headers = [
    'Content-Type: application/json',
    'webhook-id: ' . $webhookId,
    'webhook-timestamp: ' . $timestamp,
    'webhook-signature: v1,' . $signature,
];

// Display request details
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔔 Polar Webhook Test\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "📍 Endpoint: {$webhookUrl}\n";
echo "📋 Event Type: {$eventType}\n";
echo "🔑 Webhook ID: {$webhookId}\n";
echo "⏰ Timestamp: {$timestamp}\n\n";

echo "📦 Payload:\n";
echo $payloadJson . "\n\n";

echo "🔐 Headers:\n";
foreach ($headers as $header) {
    echo "  {$header}\n";
}
echo "\n";

// Send webhook request
echo "📤 Sending webhook request...\n\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Display response
echo "═══════════════════════════════════════════════════════════════\n";
echo "📥 Response\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($error) {
    echo "❌ Error: {$error}\n";
    exit(1);
}

echo "📊 HTTP Status: {$httpCode}\n\n";

// Pretty print JSON response
$decodedResponse = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "📄 Response Body:\n";
    echo json_encode($decodedResponse, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "📄 Response Body:\n";
    echo $response . "\n\n";
}

// Determine success
if ($httpCode === 200) {
    echo "✅ Success! Webhook was processed successfully.\n";

    echo "\n💡 Next Steps:\n";
    echo "  1. Check application logs: tail -f storage/logs/laravel.log\n";
    echo "  2. Verify database was updated:\n";
    echo "     SELECT * FROM users WHERE id = 1;\n";
    echo "     SELECT * FROM subscription_history ORDER BY created_at DESC LIMIT 5;\n";
} else {
    echo "❌ Failed! Webhook returned HTTP {$httpCode}\n";

    if ($httpCode === 401) {
        echo "\n💡 Troubleshooting:\n";
        echo "  • Signature verification failed\n";
        echo "  • Check POLAR_WEBHOOK_SECRET in .env\n";
        echo "  • Ensure timestamp is within 5 minute window\n";
    } elseif ($httpCode === 404) {
        echo "\n💡 Troubleshooting:\n";
        echo "  • Route not found\n";
        echo "  • Verify Laravel server is running: php artisan serve\n";
        echo "  • Clear route cache: php artisan route:clear\n";
    } elseif ($httpCode === 500) {
        echo "\n💡 Troubleshooting:\n";
        echo "  • Server error occurred\n";
        echo "  • Check logs: tail -f storage/logs/laravel.log\n";
    }
}

echo "\n";
exit($httpCode === 200 ? 0 : 1);
