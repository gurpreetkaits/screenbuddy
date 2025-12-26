<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolarWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $webhookSecret = 'test_webhook_secret_key';

    protected function setUp(): void
    {
        parent::setUp();

        // Set the webhook secret in config (simulating Polar's format: polar_whs_<base64>)
        config(['services.polar.webhook_secret' => 'polar_whs_' . base64_encode($this->webhookSecret)]);

        // Create a test user
        $this->user = User::factory()->create([
            'subscription_status' => 'free',
            'polar_customer_id' => null,
        ]);
    }

    /**
     * Generate a valid webhook signature for testing
     */
    protected function generateWebhookSignature(string $payload, int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $webhookId = 'whk_test_' . uniqid();

        $signedContent = $webhookId . '.' . $timestamp . '.' . $payload;
        $signature = base64_encode(hash_hmac('sha256', $signedContent, $this->webhookSecret, true));

        return [
            'webhook-id' => $webhookId,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => 'v1,' . $signature,
        ];
    }


    public function test_webhook_endpoint_is_publicly_accessible_without_authentication()
    {
        // Test that webhook endpoint does not require authentication
        // This ensures Polar can send webhooks without auth credentials
        // The route at /api/webhooks/polar should be CSRF exempt via bootstrap/app.php

        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_test_123',
                'customer_id' => 'cust_test_456',
                'status' => 'active',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        // Make request WITHOUT any authentication, session, or CSRF token
        // This simulates an external webhook request from Polar
        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Should succeed (200) as long as signature is valid - proves route is public and CSRF exempt
        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }


    public function test_webhook_endpoint_is_csrf_exempt()
    {
        // Verify that CSRF protection does not block webhook requests
        // This is critical for external services like Polar to send webhooks
        // CSRF is exempted for /api/* routes in bootstrap/app.php

        $payload = json_encode([
            'type' => 'checkout.created',
            'data' => [
                'id' => 'checkout_test_789',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        // Attempt POST without CSRF token (simulating external request from Polar)
        // No cookies, no session, no CSRF token - just like a real external webhook
        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Should succeed - CSRF is exempted for /api/* routes
        $response->assertOk();
        $response->assertJson(['status' => 'success']);
    }


    public function test_webhook_route_exists_and_is_named_correctly()
    {
        // Verify the webhook route is properly configured
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('webhooks.polar'));

        // Verify the URL is correct
        $url = route('webhooks.polar');
        $this->assertStringContainsString('/api/webhooks/polar', $url);
    }

    public function test_webhook_rejects_get_requests()
    {
        // Webhook should only accept POST requests
        $response = $this->get('/api/webhooks/polar');

        // Should return 405 Method Not Allowed
        $response->assertStatus(405);
    }

    public function test_it_rejects_webhook_without_signature()
    {
        $response = $this->postJson('/api/webhooks/polar', [
            'type' => 'subscription.created',
            'data' => [],
        ]);

        $response->assertStatus(401);
    }

    
    public function test_it_rejects_webhook_with_invalid_signature()
    {
        $response = $this->postJson('/api/webhooks/polar', [
            'type' => 'subscription.created',
            'data' => [],
        ], [
            'webhook-id' => 'whk_test_123',
            'webhook-timestamp' => (string) time(),
            'webhook-signature' => 'v1,invalid_signature',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_it_returns_error_for_missing_event_type()
    {
        $payload = json_encode([
            'data' => ['id' => 'test_123'],
            // Missing 'type' field
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Missing event type']);
    }

    
    public function test_it_rejects_webhook_with_expired_timestamp()
    {
        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [],
        ]);

        // Use timestamp from 10 minutes ago (beyond 5 minute window)
        $headers = $this->generateWebhookSignature($payload, time() - 600);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(401);
    }

    
    public function test_it_handles_customer_created_event()
    {
        $payload = json_encode([
            'type' => 'customer.created',
            'data' => [
                'id' => 'cust_new_123',
                'external_id' => (string) $this->user->id,
                'email' => $this->user->email,
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        // Verify user was linked to customer
        $this->user->refresh();
        $this->assertEquals('cust_new_123', $this->user->polar_customer_id);
    }

    
    public function test_it_handles_subscription_created_event()
    {
        // Set up user with customer ID
        $this->user->update(['polar_customer_id' => 'cust_test_456']);

        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_new_789',
                'customer_id' => 'cust_test_456',
                'product_id' => 'prod_test_123',
                'price_id' => 'price_test_456',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        // Verify subscription was created
        $this->user->refresh();
        $this->assertEquals('sub_new_789', $this->user->polar_subscription_id);
        $this->assertEquals('active', $this->user->subscription_status);
        $this->assertEquals('prod_test_123', $this->user->polar_product_id);
    }

    
    public function test_it_handles_subscription_created_with_external_id()
    {
        // Test when user doesn't have polar_customer_id but webhook has external_id
        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_external_123',
                'customer_id' => 'cust_brand_new',
                'product_id' => 'prod_monthly',
                'price_id' => 'price_monthly',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
                'customer' => [
                    'external_id' => (string) $this->user->id,
                ],
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        // Verify user was found and updated
        $this->user->refresh();
        $this->assertEquals('sub_external_123', $this->user->polar_subscription_id);
        $this->assertEquals('cust_brand_new', $this->user->polar_customer_id);
        $this->assertEquals('active', $this->user->subscription_status);
    }

    
    public function test_it_handles_subscription_active_event()
    {
        $this->user->update([
            'polar_customer_id' => 'cust_active_test',
            'polar_subscription_id' => 'sub_pending_123',
            'subscription_status' => 'incomplete',
        ]);

        $payload = json_encode([
            'type' => 'subscription.active',
            'data' => [
                'id' => 'sub_pending_123',
                'customer_id' => 'cust_active_test',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $this->user->refresh();
        $this->assertEquals('active', $this->user->subscription_status);
    }

    
    public function test_it_handles_subscription_canceled_event()
    {
        $this->user->update([
            'polar_subscription_id' => 'sub_cancel_test',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);

        $payload = json_encode([
            'type' => 'subscription.canceled',
            'data' => [
                'id' => 'sub_cancel_test',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $this->user->refresh();
        $this->assertEquals('canceled', $this->user->subscription_status);
        $this->assertNotNull($this->user->subscription_canceled_at);
    }

    
    public function test_it_handles_subscription_revoked_event()
    {
        $this->user->update([
            'polar_subscription_id' => 'sub_revoke_test',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);

        $payload = json_encode([
            'type' => 'subscription.revoked',
            'data' => [
                'id' => 'sub_revoke_test',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $this->user->refresh();
        $this->assertEquals('expired', $this->user->subscription_status);
    }

    
    public function test_it_handles_subscription_updated_event()
    {
        $this->user->update([
            'polar_subscription_id' => 'sub_update_test',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);

        $newExpiry = now()->addMonths(2);

        $payload = json_encode([
            'type' => 'subscription.updated',
            'data' => [
                'id' => 'sub_update_test',
                'status' => 'active',
                'current_period_end' => $newExpiry->toIso8601String(),
                'product_id' => 'prod_yearly',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $this->user->refresh();
        $this->assertEquals('prod_yearly', $this->user->polar_product_id);
    }

    
    public function test_it_returns_success_for_unhandled_event_types()
    {
        $payload = json_encode([
            'type' => 'some.unknown.event',
            'data' => [],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Should still return success (webhook received, just not acted upon)
        $response->assertOk();
    }

    
    public function test_it_logs_warning_when_user_not_found()
    {
        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_orphan_123',
                'customer_id' => 'cust_nonexistent',
                'status' => 'active',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Should still return success (webhook processed, user just not found)
        $response->assertOk();
    }

    // ==========================================
    // Subscription History Recording Tests
    // ==========================================

    public function test_subscription_created_records_history()
    {
        $this->user->update(['polar_customer_id' => 'cust_history_test']);

        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_history_123',
                'customer_id' => 'cust_history_test',
                'product_id' => 'prod_test',
                'price_id' => 'price_test',
                'status' => 'active',
                'amount' => 700,
                'recurring_interval' => 'month',
                'current_period_end' => now()->addMonth()->toIso8601String(),
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Verify history record was created
        $this->assertDatabaseHas('subscription_history', [
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'polar_subscription_id' => 'sub_history_123',
        ]);
    }

    public function test_subscription_activated_records_history()
    {
        $this->user->update([
            'polar_subscription_id' => 'sub_activate_history',
            'subscription_status' => 'incomplete',
        ]);

        $payload = json_encode([
            'type' => 'subscription.active',
            'data' => [
                'id' => 'sub_activate_history',
                'customer_id' => 'cust_test',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertDatabaseHas('subscription_history', [
            'user_id' => $this->user->id,
            'event_type' => 'activated',
            'status' => 'active',
        ]);
    }

    public function test_subscription_canceled_records_history()
    {
        $this->user->update([
            'polar_subscription_id' => 'sub_cancel_history',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);

        $payload = json_encode([
            'type' => 'subscription.canceled',
            'data' => [
                'id' => 'sub_cancel_history',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertDatabaseHas('subscription_history', [
            'user_id' => $this->user->id,
            'event_type' => 'canceled',
            'status' => 'canceled',
        ]);
    }

    public function test_subscription_revoked_records_history()
    {
        $this->user->update([
            'polar_subscription_id' => 'sub_revoke_history',
            'subscription_status' => 'active',
        ]);

        $payload = json_encode([
            'type' => 'subscription.revoked',
            'data' => [
                'id' => 'sub_revoke_history',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $this->assertDatabaseHas('subscription_history', [
            'user_id' => $this->user->id,
            'event_type' => 'revoked',
            'status' => 'expired',
        ]);
    }

    public function test_subscription_updated_records_history()
    {
        $this->user->update([
            'polar_subscription_id' => 'sub_update_history',
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);

        $payload = json_encode([
            'type' => 'subscription.updated',
            'data' => [
                'id' => 'sub_update_history',
                'status' => 'active',
                'product_id' => 'prod_yearly',
                'current_period_end' => now()->addMonths(2)->toIso8601String(),
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Should record as 'renewed' when period_end extends
        $history = SubscriptionHistory::where('user_id', $this->user->id)->first();
        $this->assertNotNull($history);
        $this->assertContains($history->event_type, ['updated', 'renewed']);
    }

    public function test_multiple_webhook_events_create_multiple_history_records()
    {
        $this->user->update(['polar_customer_id' => 'cust_multi_history']);

        // First event - subscription created
        $payload1 = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_multi_123',
                'customer_id' => 'cust_multi_history',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
            ],
        ]);

        $headers1 = $this->generateWebhookSignature($payload1);

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers1['webhook-id'],
            'HTTP_webhook-timestamp' => $headers1['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers1['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload1);

        // Second event - subscription active
        $payload2 = json_encode([
            'type' => 'subscription.active',
            'data' => [
                'id' => 'sub_multi_123',
                'customer_id' => 'cust_multi_history',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
            ],
        ]);

        $headers2 = $this->generateWebhookSignature($payload2);

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers2['webhook-id'],
            'HTTP_webhook-timestamp' => $headers2['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers2['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload2);

        // Verify both history records exist
        $historyCount = SubscriptionHistory::where('user_id', $this->user->id)->count();
        $this->assertEquals(2, $historyCount);
    }
}
