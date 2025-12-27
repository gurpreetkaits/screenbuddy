<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_it_rejects_invalid_plan_parameter()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/subscription/checkout', [
                'plan' => 'invalid_plan',
            ]);

        $response->assertStatus(422);
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

    public function test_it_returns_error_for_portal_without_customer_id()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/portal');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Customer ID not found',
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
        // Create 2 orders using the package's Order model
        \Danestves\LaravelPolar\Order::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => $this->user->id,
            'polar_id' => 'order_history_1',
            'status' => \Polar\Models\Components\OrderStatus::Paid,
            'amount' => 700,
            'tax_amount' => 0,
            'refunded_amount' => 0,
            'refunded_tax_amount' => 0,
            'currency' => 'USD',
            'billing_reason' => 'subscription_create',
            'customer_id' => 'customer_123',
            'product_id' => 'product_123',
            'ordered_at' => now()->subHour(),
        ]);

        \Danestves\LaravelPolar\Order::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => $this->user->id,
            'polar_id' => 'order_history_2',
            'status' => \Polar\Models\Components\OrderStatus::Paid,
            'amount' => 700,
            'tax_amount' => 0,
            'refunded_amount' => 0,
            'refunded_tax_amount' => 0,
            'currency' => 'USD',
            'billing_reason' => 'subscription_cycle',
            'customer_id' => 'customer_123',
            'product_id' => 'product_123',
            'ordered_at' => now(),
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
        // Create older order first
        \Danestves\LaravelPolar\Order::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => $this->user->id,
            'polar_id' => 'order_older',
            'status' => \Polar\Models\Components\OrderStatus::Paid,
            'amount' => 700,
            'tax_amount' => 0,
            'refunded_amount' => 0,
            'refunded_tax_amount' => 0,
            'currency' => 'USD',
            'billing_reason' => 'subscription_create',
            'customer_id' => 'customer_123',
            'product_id' => 'product_123',
            'ordered_at' => now()->subDay(),
        ]);

        // Create newer order
        \Danestves\LaravelPolar\Order::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => $this->user->id,
            'polar_id' => 'order_newer',
            'status' => \Polar\Models\Components\OrderStatus::Paid,
            'amount' => 700,
            'tax_amount' => 0,
            'refunded_amount' => 0,
            'refunded_tax_amount' => 0,
            'currency' => 'USD',
            'billing_reason' => 'subscription_cycle',
            'customer_id' => 'customer_123',
            'product_id' => 'product_123',
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history');
        $this->assertCount(2, $history);
        // Newest should be first
        $this->assertEquals('subscription_cycle', $history[0]['event_type']);
        $this->assertEquals('subscription_create', $history[1]['event_type']);
    }

    public function test_it_does_not_expose_polar_ids_in_history()
    {
        // Create an order using the package's Order model
        \Danestves\LaravelPolar\Order::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => $this->user->id,
            'polar_id' => 'order_secret_123',
            'status' => \Polar\Models\Components\OrderStatus::Paid,
            'amount' => 700,
            'tax_amount' => 0,
            'refunded_amount' => 0,
            'refunded_tax_amount' => 0,
            'currency' => 'USD',
            'billing_reason' => 'subscription_create',
            'customer_id' => 'cust_secret_456',
            'product_id' => 'prod_secret_789',
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history.0');

        // These sensitive IDs should not be in the response
        $this->assertArrayNotHasKey('polar_id', $history);
        $this->assertArrayNotHasKey('customer_id', $history);
        $this->assertArrayNotHasKey('product_id', $history);
        $this->assertArrayNotHasKey('billable_id', $history);
        $this->assertArrayNotHasKey('billable_type', $history);
    }

    public function test_it_returns_401_for_history_without_auth()
    {
        $response = $this->getJson('/api/subscription/history');

        $response->assertStatus(401);
    }

    public function test_history_includes_human_readable_event_labels()
    {
        // Create an order using the package's Order model
        \Danestves\LaravelPolar\Order::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => $this->user->id,
            'polar_id' => 'order_test_123',
            'status' => \Polar\Models\Components\OrderStatus::Paid,
            'amount' => 700,
            'tax_amount' => 0,
            'refunded_amount' => 0,
            'refunded_tax_amount' => 0,
            'currency' => 'USD',
            'billing_reason' => 'subscription_create',
            'customer_id' => 'customer_123',
            'product_id' => 'product_123',
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history.0');
        $this->assertEquals('Subscription Started', $history['event_label']);
    }

    public function test_history_includes_formatted_amount()
    {
        // Create an order using the package's Order model
        \Danestves\LaravelPolar\Order::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => $this->user->id,
            'polar_id' => 'order_test_456',
            'status' => \Polar\Models\Components\OrderStatus::Paid,
            'amount' => 700, // $7.00 in cents
            'tax_amount' => 0,
            'refunded_amount' => 0,
            'refunded_tax_amount' => 0,
            'currency' => 'USD',
            'billing_reason' => 'subscription_create',
            'customer_id' => 'customer_123',
            'product_id' => 'product_123',
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history.0');
        $this->assertEquals('$7.00', $history['formatted_amount']);
    }

    public function test_history_limits_to_50_records()
    {
        // Create 60 order records using the package's Order model
        for ($i = 0; $i < 60; $i++) {
            \Danestves\LaravelPolar\Order::create([
                'billable_type' => 'App\Models\User',
                'billable_id' => $this->user->id,
                'polar_id' => 'order_test_'.$i,
                'status' => \Polar\Models\Components\OrderStatus::Paid,
                'amount' => 700,
                'tax_amount' => 0,
                'refunded_amount' => 0,
                'refunded_tax_amount' => 0,
                'currency' => 'USD',
                'billing_reason' => 'subscription_cycle',
                'customer_id' => 'customer_123',
                'product_id' => 'product_123',
                'ordered_at' => now()->subDays($i),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/subscription/history');

        $response->assertOk();

        $history = $response->json('history');
        $this->assertCount(50, $history);
    }
}
