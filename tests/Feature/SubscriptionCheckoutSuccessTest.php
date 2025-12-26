<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionCheckoutSuccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'subscription_status' => 'free',
            'polar_customer_id' => null,
        ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->postJson('/api/subscription/checkout/success', [
            'checkout_id' => 'checkout_test_123',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_requires_checkout_id()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout/success', []);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Missing checkout_id parameter',
        ]);
    }

    public function test_it_fetches_checkout_and_creates_subscription()
    {
        // Mock Polar API responses
        Http::fake([
            '*/v1/checkouts/*' => Http::response([
                'id' => 'checkout_success_123',
                'status' => 'succeeded',
                'customer_id' => 'cust_new_456',
                'subscription_id' => 'sub_new_789',
            ], 200),
            '*/v1/subscriptions/*' => Http::response([
                'id' => 'sub_new_789',
                'customer_id' => 'cust_new_456',
                'product_id' => 'prod_monthly',
                'price_id' => 'price_monthly',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
                'amount' => 700,
                'recurring_interval' => 'month',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout/success', [
                'checkout_id' => 'checkout_success_123',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'subscription' => [
                'status' => 'active',
                'is_active' => true,
            ],
        ]);

        // Verify user was updated
        $this->user->refresh();
        $this->assertEquals('cust_new_456', $this->user->polar_customer_id);
        $this->assertEquals('sub_new_789', $this->user->polar_subscription_id);
        $this->assertEquals('active', $this->user->subscription_status);
        $this->assertEquals('prod_monthly', $this->user->polar_product_id);

        // Verify subscription history was recorded
        $this->assertDatabaseHas('subscription_history', [
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'polar_subscription_id' => 'sub_new_789',
        ]);
    }

    public function test_it_handles_checkout_without_subscription()
    {
        // Mock checkout response without subscription_id (checkout not completed yet)
        Http::fake([
            '*/v1/checkouts/*' => Http::response([
                'id' => 'checkout_pending_123',
                'status' => 'open',
                'customer_id' => 'cust_new_456',
                'subscription_id' => null,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout/success', [
                'checkout_id' => 'checkout_pending_123',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'subscription' => [
                'status' => 'free',
                'is_active' => false,
            ],
        ]);

        // Verify customer ID was linked but no subscription
        $this->user->refresh();
        $this->assertEquals('cust_new_456', $this->user->polar_customer_id);
        $this->assertNull($this->user->polar_subscription_id);
    }

    public function test_webhook_does_not_duplicate_subscription_history()
    {
        // First, checkout success handler creates subscription
        Http::fake([
            '*/v1/checkouts/*' => Http::response([
                'id' => 'checkout_123',
                'status' => 'succeeded',
                'customer_id' => 'cust_456',
                'subscription_id' => 'sub_789',
            ], 200),
            '*/v1/subscriptions/*' => Http::response([
                'id' => 'sub_789',
                'customer_id' => 'cust_456',
                'product_id' => 'prod_monthly',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
                'amount' => 700,
            ], 200),
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout/success', [
                'checkout_id' => 'checkout_123',
            ]);

        // Verify subscription was created
        $this->user->refresh();
        $this->assertEquals('sub_789', $this->user->polar_subscription_id);

        // Verify one history record exists
        $historyCount = SubscriptionHistory::where('user_id', $this->user->id)->count();
        $this->assertEquals(1, $historyCount);

        // Now simulate webhook arriving (should not create duplicate history)
        config(['services.polar.webhook_secret' => 'polar_whs_' . base64_encode('test_secret')]);

        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_789',
                'customer_id' => 'cust_456',
                'product_id' => 'prod_monthly',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
            ],
        ]);

        $timestamp = time();
        $webhookId = 'whk_test_duplicate';
        $signedContent = $webhookId . '.' . $timestamp . '.' . $payload;
        $signature = base64_encode(hash_hmac('sha256', $signedContent, 'test_secret', true));

        $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $webhookId,
            'HTTP_webhook-timestamp' => (string) $timestamp,
            'HTTP_webhook-signature' => 'v1,' . $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Verify still only one history record (webhook should not duplicate)
        $historyCount = SubscriptionHistory::where('user_id', $this->user->id)->count();
        $this->assertEquals(1, $historyCount);
    }

    public function test_it_handles_failed_checkout_fetch()
    {
        Http::fake([
            '*/v1/checkouts/*' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout/success', [
                'checkout_id' => 'checkout_nonexistent',
            ]);

        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Failed to verify checkout',
        ]);
    }

    public function test_it_handles_failed_subscription_fetch()
    {
        Http::fake([
            '*/v1/checkouts/*' => Http::response([
                'id' => 'checkout_123',
                'customer_id' => 'cust_456',
                'subscription_id' => 'sub_789',
            ], 200),
            '*/v1/subscriptions/*' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout/success', [
                'checkout_id' => 'checkout_123',
            ]);

        // Should still succeed even if subscription fetch fails
        $response->assertOk();
    }
}
