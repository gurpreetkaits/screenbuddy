<?php

namespace Tests\Feature;

use App\Models\User;
use Danestves\LaravelPolar\Customer;
use Danestves\LaravelPolar\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolarWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        // Use test webhook secret
        $this->webhookSecret = 'test_webhook_secret_for_testing';
        config(['webhook-client.configs.0.signing_secret' => $this->webhookSecret]);

        // Process jobs synchronously for testing
        config(['queue.default' => 'sync']);
    }

    /**
     * Generate valid webhook signature using StandardWebhooks library.
     */
    private function generateWebhookSignature(string $payload, string $webhookId, string $timestamp): string
    {
        $signingSecret = base64_encode($this->webhookSecret);
        $wh = new \StandardWebhooks\Webhook($signingSecret);

        // StandardWebhooks sign method returns the signature
        return $wh->sign($webhookId, $timestamp, $payload);
    }

    /**
     * Create webhook headers for a valid request.
     */
    private function createWebhookHeaders(string $payload): array
    {
        $webhookId = 'wh_'.bin2hex(random_bytes(16));
        $timestamp = (string) time();
        $signature = $this->generateWebhookSignature($payload, $webhookId, $timestamp);

        return [
            'webhook-id' => $webhookId,
            'webhook-timestamp' => $timestamp,
            'webhook-signature' => $signature,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Build a mock Polar subscription.created webhook payload.
     */
    private function buildSubscriptionCreatedPayload(User $user, array $overrides = []): array
    {
        $subscriptionId = $overrides['subscription_id'] ?? 'sub_'.bin2hex(random_bytes(16));
        $customerId = $overrides['customer_id'] ?? 'cust_'.bin2hex(random_bytes(16));
        $productId = $overrides['product_id'] ?? 'prod_'.bin2hex(random_bytes(16));

        $data = [
            'id' => $subscriptionId,
            'status' => $overrides['status'] ?? 'active',
            'amount' => $overrides['amount'] ?? 700,
            'currency' => $overrides['currency'] ?? 'usd',
            'recurring_interval' => $overrides['recurring_interval'] ?? 'month',
            'current_period_start' => now()->toIso8601String(),
            'current_period_end' => $overrides['current_period_end'] ?? now()->addMonth()->toIso8601String(),
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'ended_at' => null,
            'ends_at' => null,
            'started_at' => now()->toIso8601String(),
            'customer_id' => $customerId,
            'product_id' => $productId,
            'price_id' => 'price_'.bin2hex(random_bytes(8)),
            'customer' => [
                'id' => $customerId,
                'email' => $user->email,
                'metadata' => [
                    'billable_id' => $user->id,
                    'billable_type' => User::class,
                ],
            ],
            'product' => [
                'id' => $productId,
                'name' => $overrides['product_name'] ?? 'Monthly Plan',
            ],
        ];

        return [
            'type' => 'subscription.created',
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];
    }

    public function test_polar_webhook_processes_subscription_created_event_successfully(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'subscription_status' => 'free',
        ]);

        // Build webhook payload
        $webhookPayload = $this->buildSubscriptionCreatedPayload($user);
        $jsonPayload = json_encode($webhookPayload);

        // Generate valid signature headers
        $headers = $this->createWebhookHeaders($jsonPayload);

        // Send webhook request
        $response = $this->withHeaders($headers)
            ->postJson('/polar/webhook', $webhookPayload);

        // Assert webhook was accepted
        $response->assertOk();

        // Assert webhook was stored in database
        $this->assertDatabaseHas('webhook_calls', [
            'name' => 'polar',
        ]);

        // Assert polar_customers record was created
        $this->assertDatabaseHas('polar_customers', [
            'billable_id' => $user->id,
            'billable_type' => User::class,
        ]);

        // Assert polar_subscriptions record was created with active status
        $subscription = Subscription::first();
        $this->assertNotNull($subscription, 'Subscription should be created');
        $this->assertEquals('active', $subscription->status->value);

        // Assert User model was updated with subscription data
        $user->refresh();
        $this->assertEquals('active', $user->subscription_status, 'User subscription_status should be active');
        $this->assertNotNull($user->polar_subscription_id, 'User polar_subscription_id should be set');
        $this->assertNotNull($user->subscription_started_at, 'User subscription_started_at should be set');
        $this->assertNotNull($user->subscription_expires_at, 'User subscription_expires_at should be set');
    }

    public function test_polar_webhook_rejects_invalid_signature(): void
    {
        $user = User::factory()->create();

        $webhookPayload = $this->buildSubscriptionCreatedPayload($user);
        $jsonPayload = json_encode($webhookPayload);

        // Use invalid signature
        $headers = [
            'webhook-id' => 'wh_invalid',
            'webhook-timestamp' => (string) time(),
            'webhook-signature' => 'v1,invalid_signature_here',
            'Content-Type' => 'application/json',
        ];

        $response = $this->withHeaders($headers)
            ->postJson('/polar/webhook', $webhookPayload);

        // Should reject with 500 (signature validation failure)
        $response->assertStatus(500);

        // No webhook should be stored
        $this->assertDatabaseMissing('webhook_calls', [
            'name' => 'polar',
        ]);
    }

    public function test_polar_webhook_rejects_request_without_signature_headers(): void
    {
        $user = User::factory()->create();

        $webhookPayload = $this->buildSubscriptionCreatedPayload($user);

        $response = $this->postJson('/polar/webhook', $webhookPayload);

        // Should reject
        $response->assertStatus(500);
    }

    public function test_polar_webhook_creates_subscription_with_correct_data(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'subscription_status' => 'free',
        ]);

        // Build webhook payload with specific data
        $subscriptionId = 'sub_test_12345';
        $customerId = 'cust_test_67890';
        $productId = 'prod_test_abcde';

        $webhookPayload = [
            'type' => 'subscription.created',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $subscriptionId,
                'status' => 'active',
                'amount' => 4900,
                'currency' => 'usd',
                'recurring_interval' => 'year',
                'current_period_start' => now()->toIso8601String(),
                'current_period_end' => now()->addYear()->toIso8601String(),
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'ended_at' => null,
                'ends_at' => null,
                'started_at' => now()->toIso8601String(),
                'customer_id' => $customerId,
                'product_id' => $productId,
                'price_id' => 'price_yearly_123',
                'customer' => [
                    'id' => $customerId,
                    'email' => $user->email,
                    'metadata' => [
                        'billable_id' => $user->id,
                        'billable_type' => User::class,
                    ],
                ],
                'product' => [
                    'id' => $productId,
                    'name' => 'Yearly Plan',
                ],
            ],
        ];

        $jsonPayload = json_encode($webhookPayload);
        $headers = $this->createWebhookHeaders($jsonPayload);

        $response = $this->withHeaders($headers)
            ->postJson('/polar/webhook', $webhookPayload);

        $response->assertOk();

        // Verify subscription was created with correct details
        $subscription = Subscription::where('polar_id', $subscriptionId)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status->value);

        // Verify customer was linked
        $customer = Customer::where('billable_id', $user->id)->first();
        $this->assertNotNull($customer);
        $this->assertEquals($customerId, $customer->polar_id);
    }
}
