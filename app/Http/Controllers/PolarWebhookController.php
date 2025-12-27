<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PolarWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Polar
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // ============================================
        // DEBUG: Log EVERY incoming webhook request
        // ============================================
        Log::info('=== POLAR WEBHOOK INCOMING ===', [
            'timestamp' => now()->toIso8601String(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Log::info('Polar webhook headers', [
            'webhook-id' => $request->header('webhook-id'),
            'webhook-timestamp' => $request->header('webhook-timestamp'),
            'webhook-signature' => $request->header('webhook-signature') ? 'present (hidden)' : 'missing',
            'content-type' => $request->header('content-type'),
            'content-length' => $request->header('content-length'),
        ]);

        Log::info('Polar webhook raw body', [
            'body_length' => strlen($request->getContent()),
            'body_preview' => substr($request->getContent(), 0, 500),
        ]);

        // Verify webhook signature
        if (! $this->verifyWebhookSignature($request)) {
            Log::warning('Polar webhook signature verification failed', [
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Parse webhook payload
        $payload = $request->all();
        $eventType = $payload['type'] ?? null;

        if (! $eventType) {
            Log::error('Polar webhook missing event type', ['payload' => $payload]);

            return response()->json(['error' => 'Missing event type'], 400);
        }

        Log::info('Polar webhook received', [
            'event' => $eventType,
            'subscription_id' => $payload['data']['id'] ?? null,
        ]);

        try {
            // Route to appropriate handler based on event type
            switch ($eventType) {
                case 'checkout.created':
                case 'checkout.updated':
                    // Checkout in progress, no action needed
                    Log::info('Checkout event received', ['event' => $eventType]);
                    break;

                case 'subscription.created':
                    $this->handleSubscriptionCreated($payload);
                    break;

                case 'subscription.updated':
                    $this->handleSubscriptionUpdated($payload);
                    break;

                case 'subscription.active':
                    $this->handleSubscriptionActive($payload);
                    break;

                case 'subscription.canceled':
                    $this->handleSubscriptionCanceled($payload);
                    break;

                case 'subscription.revoked':
                    $this->handleSubscriptionRevoked($payload);
                    break;

                case 'order.created':
                    $this->handleOrderCreated($payload);
                    break;

                case 'customer.created':
                    $this->handleCustomerCreated($payload);
                    break;

                case 'customer.updated':
                    $this->handleCustomerUpdated($payload);
                    break;

                default:
                    Log::info('Polar webhook event not handled', ['event' => $eventType]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing Polar webhook', [
                'event' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Verify webhook signature using Standard Webhooks library
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.polar.webhook_secret');

        if (! $secret) {
            Log::error('Polar webhook secret not configured');

            return false;
        }

        $rawBody = $request->getContent();
        $headers = [
            'webhook-id' => $request->header('webhook-id'),
            'webhook-timestamp' => $request->header('webhook-timestamp'),
            'webhook-signature' => $request->header('webhook-signature'),
        ];

        Log::debug('Webhook verification attempt', [
            'webhook_id' => $headers['webhook-id'],
            'timestamp' => $headers['webhook-timestamp'],
            'body_length' => strlen($rawBody),
        ]);

        // Convert Polar's polar_whs_ prefix to Standard Webhooks' whsec_ prefix
        $standardSecret = $secret;
        if (str_starts_with($secret, 'polar_whs_')) {
            $standardSecret = 'whsec_'.substr($secret, 10);
        }

        // Try with the official Standard Webhooks library first
        try {
            $webhook = new \StandardWebhooks\Webhook($standardSecret);
            $result = $webhook->verify($rawBody, $headers);
            Log::info('Webhook signature verified via Standard Webhooks library');

            return true;
        } catch (\StandardWebhooks\Exception\WebhookVerificationException $e) {
            Log::debug('Standard Webhooks verification failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: Try with raw secret (no base64 decoding)
        try {
            $secretWithoutPrefix = $secret;
            if (str_starts_with($secret, 'polar_whs_')) {
                $secretWithoutPrefix = substr($secret, 10);
            }
            $webhook = \StandardWebhooks\Webhook::fromRaw($secretWithoutPrefix);
            $result = $webhook->verify($rawBody, $headers);
            Log::info('Webhook signature verified via fromRaw');

            return true;
        } catch (\StandardWebhooks\Exception\WebhookVerificationException $e) {
            Log::debug('Raw secret verification failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Last fallback: Manual verification with multiple approaches
        return $this->verifyManually($request, $secret);
    }

    /**
     * Manual signature verification as fallback
     */
    protected function verifyManually(Request $request, string $secret): bool
    {
        $webhookId = $request->header('webhook-id');
        $webhookTimestamp = $request->header('webhook-timestamp');
        $webhookSignature = $request->header('webhook-signature');

        if (! $webhookId || ! $webhookTimestamp || ! $webhookSignature) {
            return false;
        }

        $rawBody = $request->getContent();
        $signedContent = $webhookId.'.'.$webhookTimestamp.'.'.$rawBody;

        // Parse received signatures
        $signatures = explode(' ', $webhookSignature);

        // Try multiple secret interpretations
        $secretVariants = $this->getSecretVariants($secret);

        foreach ($signatures as $versionedSig) {
            $parts = explode(',', $versionedSig, 2);
            if (count($parts) !== 2 || $parts[0] !== 'v1') {
                continue;
            }

            $receivedSignature = $parts[1];

            foreach ($secretVariants as $variantName => $secretBytes) {
                // Method 1: Standard Webhooks format (hex then pack then base64)
                $hexHash = hash_hmac('sha256', $signedContent, $secretBytes);
                $expectedSig1 = base64_encode(pack('H*', $hexHash));

                // Method 2: Direct binary to base64
                $expectedSig2 = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

                Log::debug('Manual verification attempt', [
                    'variant' => $variantName,
                    'expected_hex_pack' => $expectedSig1,
                    'expected_binary' => $expectedSig2,
                    'received' => $receivedSignature,
                ]);

                if (hash_equals($expectedSig1, $receivedSignature)) {
                    Log::info('Webhook verified via manual hex-pack method', ['variant' => $variantName]);

                    return true;
                }

                if (hash_equals($expectedSig2, $receivedSignature)) {
                    Log::info('Webhook verified via manual binary method', ['variant' => $variantName]);

                    return true;
                }
            }
        }

        Log::warning('All signature verification methods failed');

        return false;
    }

    /**
     * Get different interpretations of the webhook secret
     */
    protected function getSecretVariants(string $secret): array
    {
        $variants = [];

        // Remove polar_whs_ prefix if present
        $withoutPrefix = $secret;
        if (str_starts_with($secret, 'polar_whs_')) {
            $withoutPrefix = substr($secret, 10);
        }

        // Variant 1: Secret without prefix, base64 decoded (Standard Webhooks format)
        $decoded = base64_decode($withoutPrefix, true);
        if ($decoded !== false && strlen($decoded) > 0) {
            $variants['base64_decoded'] = $decoded;
        }

        // Variant 2: Secret without prefix used directly as raw bytes
        $variants['raw_without_prefix'] = $withoutPrefix;

        // Variant 3: Full secret used as raw bytes
        $variants['raw_full'] = $secret;

        return $variants;
    }

    /**
     * Handle subscription.created event
     */
    protected function handleSubscriptionCreated(array $payload): void
    {
        $data = $payload['data'];
        $customerId = $data['customer_id'] ?? null;
        $subscriptionId = $data['id'] ?? null;
        $productId = $data['product_id'] ?? null;
        $priceId = $data['price_id'] ?? null;
        $status = $data['status'] ?? null;

        // DEBUG: Log the entire payload to see what Polar actually sends
        Log::debug('Polar subscription.created full payload', [
            'full_payload' => $payload,
            'customer_id' => $customerId,
            'has_customer_object' => isset($data['customer']),
            'has_metadata' => isset($data['metadata']),
        ]);

        // Get customer external_id from nested customer object or metadata
        $customer = $data['customer'] ?? null;
        $externalId = $customer['external_id'] ?? ($data['metadata']['user_id'] ?? null);

        Log::debug('Extracted user identification', [
            'customer_object' => $customer,
            'external_id' => $externalId,
            'metadata' => $data['metadata'] ?? null,
        ]);

        if (! $subscriptionId) {
            Log::error('Missing subscription ID in subscription.created', ['data' => $data]);

            return;
        }

        // If external_id is missing, try to fetch customer data from Polar API
        if (! $externalId && $customerId) {
            Log::info('External ID not in webhook payload, fetching customer from Polar API', [
                'customer_id' => $customerId,
            ]);
            $externalId = $this->fetchCustomerExternalId($customerId);
        }

        // Find user - try multiple methods
        $user = $this->findUserForSubscription($customerId, $externalId);

        if (! $user) {
            Log::warning('User not found for subscription.created', [
                'customer_id' => $customerId,
                'external_id' => $externalId,
                'attempted_methods' => [
                    'polar_customer_id' => $customerId,
                    'external_id' => $externalId,
                ],
            ]);

            return;
        }

        Log::info('Successfully matched user for subscription', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'matched_via' => $externalId ? 'external_id' : 'customer_id',
        ]);

        // Update user with customer ID if not set
        if ($customerId && ! $user->polar_customer_id) {
            $user->polar_customer_id = $customerId;
        }

        // Check if subscription already exists (could have been created by checkout success handler)
        $subscriptionAlreadyExists = $user->polar_subscription_id === $subscriptionId;

        $periodEnd = isset($data['current_period_end'])
            ? \Carbon\Carbon::parse($data['current_period_end'])
            : null;

        // Update user subscription
        $user->update([
            'polar_customer_id' => $user->polar_customer_id ?? $customerId,
            'polar_subscription_id' => $subscriptionId,
            'polar_product_id' => $productId,
            'polar_price_id' => $priceId,
            'subscription_status' => $this->mapPolarStatus($status),
            'subscription_started_at' => $subscriptionAlreadyExists ? $user->subscription_started_at : now(),
            'subscription_expires_at' => $periodEnd,
        ]);

        // Only record history if this is a new subscription (not already created by checkout handler)
        if (! $subscriptionAlreadyExists) {
            SubscriptionHistory::recordEvent($user, 'created', $this->mapPolarStatus($status), [
                'subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
                'product_id' => $productId,
                'price_id' => $priceId,
                'period_start' => now(),
                'period_end' => $periodEnd,
                'amount' => $data['amount'] ?? null,
                'plan_name' => $this->getPlanName($productId),
                'plan_interval' => $data['recurring_interval'] ?? null,
                'metadata' => ['raw_status' => $status, 'source' => 'webhook'],
            ]);
        }

        Log::info('Subscription created for user', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'status' => $status,
            'already_existed' => $subscriptionAlreadyExists,
        ]);
    }

    /**
     * Handle subscription.active event (subscription becomes active)
     */
    protected function handleSubscriptionActive(array $payload): void
    {
        $data = $payload['data'];
        $subscriptionId = $data['id'] ?? null;
        $customerId = $data['customer_id'] ?? null;
        $customer = $data['customer'] ?? null;
        $externalId = $customer['external_id'] ?? ($data['metadata']['user_id'] ?? null);

        if (! $subscriptionId) {
            Log::error('Missing subscription ID in subscription.active');

            return;
        }

        // Try to find user by subscription ID first, then by customer
        $user = User::where('polar_subscription_id', $subscriptionId)->first();

        if (! $user) {
            $user = $this->findUserForSubscription($customerId, $externalId);
        }

        if (! $user) {
            Log::warning('User not found for subscription.active', [
                'subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
            ]);

            return;
        }

        $periodEnd = isset($data['current_period_end'])
            ? \Carbon\Carbon::parse($data['current_period_end'])
            : null;

        // Update to active status
        $user->update([
            'subscription_status' => 'active',
            'polar_subscription_id' => $subscriptionId,
            'polar_customer_id' => $user->polar_customer_id ?? $customerId,
            'subscription_expires_at' => $periodEnd,
        ]);

        // Record subscription history
        SubscriptionHistory::recordEvent($user, 'activated', 'active', [
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'product_id' => $data['product_id'] ?? null,
            'period_end' => $periodEnd,
        ]);

        Log::info('Subscription activated for user', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
        ]);
    }

    /**
     * Handle customer.created event - link Polar customer to our user
     */
    protected function handleCustomerCreated(array $payload): void
    {
        $data = $payload['data'];
        $customerId = $data['id'] ?? null;
        $externalId = $data['external_id'] ?? null;
        $email = $data['email'] ?? null;

        if (! $customerId) {
            Log::error('Missing customer ID in customer.created');

            return;
        }

        // Find user by external_id (our user ID) or email
        $user = null;

        if ($externalId) {
            $user = User::find($externalId);
        }

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            Log::warning('User not found for customer.created', [
                'customer_id' => $customerId,
                'external_id' => $externalId,
                'email' => $email,
            ]);

            return;
        }

        // Link customer ID to user
        $user->update(['polar_customer_id' => $customerId]);

        Log::info('Customer linked to user', [
            'user_id' => $user->id,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Fetch customer's external_id from Polar API
     */
    protected function fetchCustomerExternalId(?string $customerId): ?string
    {
        if (! $customerId) {
            return null;
        }

        try {
            $apiKey = config('services.polar.api_key');
            $apiUrl = config('services.polar.api_url', 'https://api.polar.sh');

            if (! $apiKey) {
                Log::error('Polar API key not configured, cannot fetch customer data');

                return null;
            }

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Accept' => 'application/json',
            ])->get("{$apiUrl}/v1/customers/{$customerId}");

            if ($response->successful()) {
                $customerData = $response->json();
                Log::debug('Fetched customer from Polar API', [
                    'customer_id' => $customerId,
                    'external_id' => $customerData['external_id'] ?? null,
                    'email' => $customerData['email'] ?? null,
                ]);

                return $customerData['external_id'] ?? null;
            }

            Log::warning('Failed to fetch customer from Polar API', [
                'customer_id' => $customerId,
                'status' => $response->status(),
                'error' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching customer from Polar API', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find user for subscription by various identifiers
     */
    protected function findUserForSubscription(?string $customerId, ?string $externalId): ?User
    {
        // Try by polar_customer_id first
        if ($customerId) {
            $user = User::where('polar_customer_id', $customerId)->first();
            if ($user) {
                Log::debug('User found via polar_customer_id', [
                    'user_id' => $user->id,
                    'polar_customer_id' => $customerId,
                ]);

                return $user;
            }
        }

        // Try by external_id (our user ID)
        if ($externalId) {
            $user = User::find($externalId);
            if ($user) {
                Log::debug('User found via external_id', [
                    'user_id' => $user->id,
                    'external_id' => $externalId,
                ]);

                return $user;
            }
        }

        return null;
    }

    /**
     * Handle subscription.updated event
     */
    protected function handleSubscriptionUpdated(array $payload): void
    {
        $data = $payload['data'];
        $subscriptionId = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        if (! $subscriptionId) {
            Log::error('Missing subscription ID in subscription.updated');

            return;
        }

        $user = User::where('polar_subscription_id', $subscriptionId)->first();

        if (! $user) {
            Log::warning('User not found for subscription ID', ['subscription_id' => $subscriptionId]);

            return;
        }

        $periodEnd = isset($data['current_period_end'])
            ? \Carbon\Carbon::parse($data['current_period_end'])
            : null;

        // Update subscription details
        $updateData = [
            'subscription_status' => $this->mapPolarStatus($status),
        ];

        if ($periodEnd) {
            $updateData['subscription_expires_at'] = $periodEnd;
        }

        if (isset($data['product_id'])) {
            $updateData['polar_product_id'] = $data['product_id'];
        }

        if (isset($data['price_id'])) {
            $updateData['polar_price_id'] = $data['price_id'];
        }

        $user->update($updateData);

        // Check if this is a renewal (period_end changed)
        $eventType = 'updated';
        if ($periodEnd && $user->subscription_expires_at && $periodEnd->gt($user->subscription_expires_at)) {
            $eventType = 'renewed';
        }

        // Record subscription history
        SubscriptionHistory::recordEvent($user, $eventType, $this->mapPolarStatus($status), [
            'subscription_id' => $subscriptionId,
            'product_id' => $data['product_id'] ?? null,
            'price_id' => $data['price_id'] ?? null,
            'period_end' => $periodEnd,
            'amount' => $data['amount'] ?? null,
        ]);

        Log::info('Subscription updated for user', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'status' => $status,
        ]);
    }

    /**
     * Handle subscription.canceled event
     */
    protected function handleSubscriptionCanceled(array $payload): void
    {
        $data = $payload['data'];
        $subscriptionId = $data['id'] ?? null;

        if (! $subscriptionId) {
            Log::error('Missing subscription ID in subscription.canceled');

            return;
        }

        $user = User::where('polar_subscription_id', $subscriptionId)->first();

        if (! $user) {
            Log::warning('User not found for subscription ID', ['subscription_id' => $subscriptionId]);

            return;
        }

        // Mark as canceled but keep access until end of billing period
        $user->update([
            'subscription_status' => 'canceled',
            'subscription_canceled_at' => now(),
            // Keep subscription_expires_at to maintain access during grace period
        ]);

        // Record subscription history
        SubscriptionHistory::recordEvent($user, 'canceled', 'canceled', [
            'subscription_id' => $subscriptionId,
            'period_end' => $user->subscription_expires_at,
            'metadata' => ['canceled_at' => now()->toIso8601String()],
        ]);

        Log::info('Subscription canceled for user', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'expires_at' => $user->subscription_expires_at,
        ]);
    }

    /**
     * Handle subscription.revoked event (immediate termination)
     */
    protected function handleSubscriptionRevoked(array $payload): void
    {
        $data = $payload['data'];
        $subscriptionId = $data['id'] ?? null;

        if (! $subscriptionId) {
            Log::error('Missing subscription ID in subscription.revoked');

            return;
        }

        $user = User::where('polar_subscription_id', $subscriptionId)->first();

        if (! $user) {
            Log::warning('User not found for subscription ID', ['subscription_id' => $subscriptionId]);

            return;
        }

        // Immediately revoke access
        $user->update([
            'subscription_status' => 'expired',
            'subscription_expires_at' => now(),
        ]);

        // Record subscription history
        SubscriptionHistory::recordEvent($user, 'revoked', 'expired', [
            'subscription_id' => $subscriptionId,
            'metadata' => ['revoked_at' => now()->toIso8601String()],
        ]);

        Log::info('Subscription revoked for user', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
        ]);
    }

    /**
     * Handle order.created event
     */
    protected function handleOrderCreated(array $payload): void
    {
        $data = $payload['data'];
        $customerId = $data['customer_id'] ?? null;
        $billingReason = $data['billing_reason'] ?? null;

        Log::info('Order created', [
            'customer_id' => $customerId,
            'billing_reason' => $billingReason,
            'amount' => $data['amount'] ?? null,
        ]);

        // Can be used for tracking renewals, upgrades, etc.
    }

    /**
     * Handle customer.updated event
     */
    protected function handleCustomerUpdated(array $payload): void
    {
        $data = $payload['data'];
        $customerId = $data['id'] ?? null;

        if (! $customerId) {
            Log::error('Missing customer ID in customer.updated');

            return;
        }

        $user = User::where('polar_customer_id', $customerId)->first();

        if (! $user) {
            Log::warning('User not found for customer ID', ['customer_id' => $customerId]);

            return;
        }

        // Update customer information if needed
        Log::info('Customer updated', [
            'user_id' => $user->id,
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Map Polar subscription status to our internal status
     */
    protected function mapPolarStatus(?string $polarStatus): string
    {
        return match ($polarStatus) {
            'active' => 'active',
            'canceled' => 'canceled',
            'incomplete', 'incomplete_expired' => 'incomplete',
            'trialing' => 'active', // Treat trial as active
            'past_due', 'unpaid' => 'expired',
            default => 'free',
        };
    }

    /**
     * Get plan name from product ID
     */
    protected function getPlanName(?string $productId): ?string
    {
        if (! $productId) {
            return null;
        }

        $monthlyProductId = config('services.polar.product_id_monthly');
        $yearlyProductId = config('services.polar.product_id_yearly');

        if ($productId === $monthlyProductId) {
            return 'Monthly';
        }

        if ($productId === $yearlyProductId) {
            return 'Yearly';
        }

        return 'Pro';
    }
}
