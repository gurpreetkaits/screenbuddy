#!/usr/bin/env php
<?php

/**
 * Test Polar Checkout Creation
 *
 * This script tests creating a checkout session with Polar.
 *
 * Usage:
 *   php scripts/test-checkout-creation.php [user_id]
 *
 * Example:
 *   php scripts/test-checkout-creation.php 4
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Get user ID from command line or use first user
$userId = $argv[1] ?? null;

if (!$userId) {
    echo "❌ Please provide a user ID\n";
    echo "Usage: php scripts/test-checkout-creation.php [user_id]\n";
    exit(1);
}

$user = User::find($userId);

if (!$user) {
    echo "❌ User with ID {$userId} not found\n";
    exit(1);
}

echo "Testing Checkout Creation\n";
echo "=========================\n\n";

echo "User: {$user->name} ({$user->email})\n";
echo "User ID: {$user->id}\n";
echo "Existing Polar Customer ID: " . ($user->polar_customer_id ?? 'None') . "\n\n";

// Validate existing customer ID if present
if ($user->polar_customer_id) {
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    $isValidUuid = preg_match($pattern, $user->polar_customer_id);

    if (!$isValidUuid) {
        echo "⚠️  WARNING: User has invalid customer ID (not a UUID)\n";
        echo "   Clearing invalid ID: {$user->polar_customer_id}\n";
        $user->update(['polar_customer_id' => null]);
        echo "   ✓ Cleared\n\n";
    } else {
        echo "✓ Customer ID is valid UUID\n\n";
    }
}

// Get configuration
$apiKey = config('services.polar.api_key');
$apiUrl = config('services.polar.api_url');
$productIdMonthly = config('services.polar.product_id_monthly');
$frontendUrl = config('services.frontend.url');
$environment = config('services.polar.environment');

echo "Configuration:\n";
echo "  Environment: {$environment}\n";
echo "  API URL: {$apiUrl}\n";
echo "  API Key: " . (substr($apiKey, 0, 15) . '...') . "\n";
echo "  Product ID (Monthly): {$productIdMonthly}\n";
echo "  Frontend URL: {$frontendUrl}\n\n";

// Build checkout payload
$checkoutPayload = [
    'products' => [$productIdMonthly],
    'success_url' => $frontendUrl . '/subscription/success?checkout_id={CHECKOUT_ID}',
    'customer_email' => $user->email,
    'customer_name' => $user->name,
    'customer_external_id' => (string) $user->id,
    'metadata' => [
        'user_id' => (string) $user->id,
        'plan' => 'monthly',
    ],
];

// If user has existing customer ID, use it
if ($user->polar_customer_id) {
    $checkoutPayload['customer_id'] = $user->polar_customer_id;
    unset($checkoutPayload['customer_email']);
    unset($checkoutPayload['customer_name']);
    echo "Using existing customer ID in request\n\n";
}

echo "Creating checkout session...\n";

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->post($apiUrl . '/v1/checkouts/', $checkoutPayload);

    if ($response->successful()) {
        $data = $response->json();

        echo "\n✅ SUCCESS!\n\n";
        echo "Checkout ID: {$data['id']}\n";
        echo "Checkout URL: {$data['url']}\n\n";
        echo "Open this URL in your browser to complete payment:\n";
        echo "{$data['url']}\n\n";

        exit(0);
    } else {
        echo "\n❌ FAILED\n\n";
        echo "Status Code: {$response->status()}\n";
        echo "Error Response:\n";
        echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n\n";

        echo "Request Payload:\n";
        echo json_encode($checkoutPayload, JSON_PRETTY_PRINT) . "\n\n";

        exit(1);
    }
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION\n\n";
    echo "Error: {$e->getMessage()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
