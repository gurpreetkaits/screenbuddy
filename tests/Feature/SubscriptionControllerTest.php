<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'subscription_status' => 'free',
            'videos_count' => 0,
        ]);
    }

    
    public function test_it_returns_subscription_status_for_authenticated_user()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/status');

        $response->assertOk()
            ->assertJsonStructure([
                'subscription' => [
                    'status',
                    'is_active',
                    'can_record',
                    'videos_count',
                    'remaining_quota',
                    'started_at',
                    'expires_at',
                    'canceled_at',
                    'is_in_grace_period',
                ],
            ]);
    }

    
    public function test_it_returns_401_for_unauthenticated_user()
    {
        $response = $this->getJson('/api/subscription/status');

        $response->assertStatus(401);
    }

    
    public function test_it_shows_free_user_can_record_one_video()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/status');

        $response->assertOk()
            ->assertJson([
                'subscription' => [
                    'status' => 'free',
                    'is_active' => false,
                    'can_record' => true,
                    'remaining_quota' => 1,
                ],
            ]);
    }

    
    public function test_it_shows_free_user_cannot_record_after_limit()
    {
        $this->user->update(['videos_count' => 1]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/status');

        $response->assertOk()
            ->assertJson([
                'subscription' => [
                    'status' => 'free',
                    'is_active' => false,
                    'can_record' => false,
                    'remaining_quota' => 0,
                ],
            ]);
    }

    
    public function test_it_shows_active_subscription_user_has_unlimited_quota()
    {
        $this->user->update([
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'subscription_expires_at' => now()->addMonth(),
            'videos_count' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/status');

        $response->assertOk()
            ->assertJson([
                'subscription' => [
                    'status' => 'active',
                    'is_active' => true,
                    'can_record' => true,
                    'remaining_quota' => null, // unlimited
                ],
            ]);
    }

    
    public function test_it_creates_checkout_session_for_monthly_plan()
    {
        // Mock the Polar API response
        Http::fake([
            '*/v1/checkouts/' => Http::response([
                'id' => 'checkout_test_123',
                'url' => 'https://checkout.polar.sh/test_checkout',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout', [
                'plan' => 'monthly',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'checkout_url',
                'checkout_id',
            ])
            ->assertJson([
                'checkout_id' => 'checkout_test_123',
            ]);
    }

    
    public function test_it_creates_checkout_session_for_yearly_plan()
    {
        Http::fake([
            '*/v1/checkouts/' => Http::response([
                'id' => 'checkout_yearly_123',
                'url' => 'https://checkout.polar.sh/test_yearly',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout', [
                'plan' => 'yearly',
            ]);

        $response->assertOk()
            ->assertJson([
                'checkout_id' => 'checkout_yearly_123',
            ]);
    }

    
    public function test_it_defaults_to_monthly_plan_when_not_specified()
    {
        Http::fake([
            '*/v1/checkouts/' => Http::response([
                'id' => 'checkout_monthly_default',
                'url' => 'https://checkout.polar.sh/monthly',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout');

        $response->assertOk();

        // Verify the request was made to Polar
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/checkouts/');
        });
    }

    
    public function test_it_rejects_invalid_plan_parameter()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout', [
                'plan' => 'invalid_plan',
            ]);

        $response->assertStatus(422);
    }

    
    public function test_it_uses_existing_polar_customer_id_for_checkout()
    {
        $this->user->update(['polar_customer_id' => 'cust_existing_123']);

        Http::fake([
            '*/v1/checkouts/' => Http::response([
                'id' => 'checkout_existing_customer',
                'url' => 'https://checkout.polar.sh/existing',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout', [
                'plan' => 'monthly',
            ]);

        $response->assertOk();

        // Verify checkout was created with customer_id
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['customer_id']) && $body['customer_id'] === 'cust_existing_123';
        });
    }

    
    public function test_it_cancels_active_subscription()
    {
        $this->user->update([
            'subscription_status' => 'active',
            'polar_subscription_id' => 'sub_test_123',
            'subscription_expires_at' => now()->addMonth(),
        ]);

        Http::fake([
            '*/v1/subscriptions/sub_test_123' => Http::response([], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/cancel');

        $response->assertOk()
            ->assertJson([
                'message' => 'Subscription canceled successfully',
            ]);

        // Verify user status was updated
        $this->user->refresh();
        $this->assertEquals('canceled', $this->user->subscription_status);
        $this->assertNotNull($this->user->subscription_canceled_at);
    }

    
    public function test_it_returns_error_when_canceling_without_active_subscription()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/cancel');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'No active subscription to cancel',
            ]);
    }

    
    public function test_it_gets_billing_portal_url()
    {
        $this->user->update(['polar_customer_id' => 'cust_portal_test']);

        Http::fake([
            '*/v1/customer-portal/sessions' => Http::response([
                'url' => 'https://portal.polar.sh/session/test',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/portal');

        $response->assertOk()
            ->assertJson([
                'portal_url' => 'https://portal.polar.sh/session/test',
            ]);
    }

    
    public function test_it_returns_error_for_portal_without_customer_id()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/portal');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Customer ID not found',
            ]);
    }

    
    public function test_it_handles_polar_api_error_gracefully()
    {
        Http::fake([
            '*/v1/checkouts/' => Http::response([
                'error' => 'Internal server error',
            ], 500),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout', [
                'plan' => 'monthly',
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Failed to create checkout session',
            ]);
    }

    // ==========================================
    // Subscription History Tests
    // ==========================================

    public function test_it_returns_empty_history_for_new_user()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk()
            ->assertJson([
                'history' => [],
            ]);
    }

    public function test_it_returns_subscription_history_for_user_with_history()
    {
        // Create some history records
        SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'polar_subscription_id' => 'sub_test_123',
            'polar_customer_id' => 'cust_test_123',
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'amount' => 700,
            'currency' => 'USD',
            'plan_name' => 'Monthly',
            'plan_interval' => 'month',
        ]);

        SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'activated',
            'status' => 'active',
            'polar_subscription_id' => 'sub_test_123',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk()
            ->assertJsonStructure([
                'history' => [
                    '*' => [
                        'id',
                        'event_type',
                        'event_label',
                        'status',
                        'period_start',
                        'period_end',
                        'amount',
                        'formatted_amount',
                        'currency',
                        'plan_name',
                        'plan_interval',
                        'created_at',
                    ],
                ],
            ]);

        $history = $response->json('history');
        $this->assertCount(2, $history);
    }

    public function test_it_returns_history_in_descending_order()
    {
        // Create older record first
        $older = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'created_at' => now()->subDay(),
        ]);

        // Create newer record
        $newer = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'renewed',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history');
        $this->assertCount(2, $history);
        // Newest should be first
        $this->assertEquals('renewed', $history[0]['event_type']);
        $this->assertEquals('created', $history[1]['event_type']);
    }

    public function test_it_does_not_expose_polar_ids_in_history()
    {
        SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'polar_subscription_id' => 'sub_secret_123',
            'polar_customer_id' => 'cust_secret_456',
            'polar_product_id' => 'prod_secret_789',
            'polar_price_id' => 'price_secret_000',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history.0');

        // These sensitive IDs should not be in the response
        $this->assertArrayNotHasKey('polar_subscription_id', $history);
        $this->assertArrayNotHasKey('polar_customer_id', $history);
        $this->assertArrayNotHasKey('polar_product_id', $history);
        $this->assertArrayNotHasKey('polar_price_id', $history);
    }

    public function test_it_returns_401_for_history_without_auth()
    {
        $response = $this->getJson('/api/subscription/history');

        $response->assertStatus(401);
    }

    public function test_history_includes_human_readable_event_labels()
    {
        SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history.0');
        $this->assertEquals('Subscription Started', $history['event_label']);
    }

    public function test_history_includes_formatted_amount()
    {
        SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'amount' => 700, // $7.00 in cents
            'currency' => 'USD',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history.0');
        $this->assertEquals('$7.00', $history['formatted_amount']);
    }

    public function test_history_limits_to_50_records()
    {
        // Create 60 history records
        for ($i = 0; $i < 60; $i++) {
            SubscriptionHistory::create([
                'user_id' => $this->user->id,
                'event_type' => 'updated',
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history');
        $this->assertCount(50, $history);
    }
}
