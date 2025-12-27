<?php

namespace Tests\Feature;

use App\Models\SubscriptionHistory;
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
