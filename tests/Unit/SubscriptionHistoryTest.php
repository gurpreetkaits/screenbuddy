<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_can_create_subscription_history()
    {
        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('subscription_history', [
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
        ]);
    }

    public function test_record_event_helper_creates_history()
    {
        $history = SubscriptionHistory::recordEvent($this->user, 'created', 'active', [
            'subscription_id' => 'sub_test_123',
            'customer_id' => 'cust_test_456',
            'product_id' => 'prod_test_789',
            'amount' => 700,
            'plan_name' => 'Monthly',
            'plan_interval' => 'month',
        ]);

        $this->assertInstanceOf(SubscriptionHistory::class, $history);
        $this->assertEquals('created', $history->event_type);
        $this->assertEquals('active', $history->status);
        $this->assertEquals('sub_test_123', $history->polar_subscription_id);
        $this->assertEquals(700, $history->amount);
        $this->assertEquals('Monthly', $history->plan_name);
    }

    public function test_event_label_accessor_returns_human_readable_labels()
    {
        $testCases = [
            'created' => 'Subscription Started',
            'activated' => 'Subscription Activated',
            'renewed' => 'Subscription Renewed',
            'canceled' => 'Subscription Canceled',
            'revoked' => 'Subscription Revoked',
            'updated' => 'Subscription Updated',
            'unknown' => 'Unknown',
        ];

        foreach ($testCases as $eventType => $expectedLabel) {
            $history = new SubscriptionHistory([
                'user_id' => $this->user->id,
                'event_type' => $eventType,
                'status' => 'active',
            ]);

            $this->assertEquals($expectedLabel, $history->event_label, "Failed for event type: $eventType");
        }
    }

    public function test_formatted_amount_accessor_formats_cents_to_dollars()
    {
        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'amount' => 700,
            'currency' => 'USD',
        ]);

        $this->assertEquals('$7.00', $history->formatted_amount);
    }

    public function test_formatted_amount_handles_larger_amounts()
    {
        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'amount' => 8000, // $80.00
            'currency' => 'USD',
        ]);

        $this->assertEquals('$80.00', $history->formatted_amount);
    }

    public function test_formatted_amount_returns_null_when_no_amount()
    {
        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
        ]);

        $this->assertNull($history->formatted_amount);
    }

    public function test_it_belongs_to_user()
    {
        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(User::class, $history->user);
        $this->assertEquals($this->user->id, $history->user->id);
    }

    public function test_user_has_many_subscription_history()
    {
        SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
        ]);

        SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'activated',
            'status' => 'active',
        ]);

        $this->assertCount(2, $this->user->subscriptionHistory);
    }

    public function test_hidden_attributes_are_not_serialized()
    {
        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'polar_subscription_id' => 'sub_secret_123',
            'polar_customer_id' => 'cust_secret_456',
            'polar_product_id' => 'prod_secret_789',
            'polar_price_id' => 'price_secret_000',
        ]);

        $array = $history->toArray();

        $this->assertArrayNotHasKey('polar_subscription_id', $array);
        $this->assertArrayNotHasKey('polar_customer_id', $array);
        $this->assertArrayNotHasKey('polar_product_id', $array);
        $this->assertArrayNotHasKey('polar_price_id', $array);
    }

    public function test_period_dates_are_cast_to_datetime()
    {
        $periodStart = now();
        $periodEnd = now()->addMonth();

        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $history->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $history->period_start);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $history->period_end);
    }

    public function test_metadata_is_cast_to_array()
    {
        $metadata = ['key' => 'value', 'nested' => ['data' => true]];

        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'metadata' => $metadata,
        ]);

        $history->refresh();

        $this->assertIsArray($history->metadata);
        $this->assertEquals('value', $history->metadata['key']);
        $this->assertTrue($history->metadata['nested']['data']);
    }

    public function test_amount_is_cast_to_integer()
    {
        $history = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'amount' => '700',
        ]);

        $history->refresh();

        $this->assertIsInt($history->amount);
        $this->assertEquals(700, $history->amount);
    }

    public function test_currency_defaults_to_usd()
    {
        $history = SubscriptionHistory::recordEvent($this->user, 'created', 'active', [
            'amount' => 700,
        ]);

        $this->assertEquals('USD', $history->currency);
    }

    public function test_subscription_history_ordered_by_created_at_desc()
    {
        $older = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'created',
            'status' => 'active',
            'created_at' => now()->subDay(),
        ]);

        $newer = SubscriptionHistory::create([
            'user_id' => $this->user->id,
            'event_type' => 'renewed',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $history = $this->user->subscriptionHistory()->get();

        $this->assertEquals($newer->id, $history->first()->id);
        $this->assertEquals($older->id, $history->last()->id);
    }
}
