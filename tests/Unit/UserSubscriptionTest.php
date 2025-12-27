<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_does_not_have_active_subscription()
    {
        $user = User::factory()->create([
            'subscription_status' => 'free',
        ]);

        $this->assertFalse($user->hasActiveSubscription());
    }

    public function test_active_user_has_active_subscription()
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
        ]);

        $this->assertTrue($user->hasActiveSubscription());
    }

    public function test_canceled_user_does_not_have_active_subscription()
    {
        $user = User::factory()->create([
            'subscription_status' => 'canceled',
        ]);

        $this->assertFalse($user->hasActiveSubscription());
    }

    public function test_free_user_can_record_first_video()
    {
        $user = User::factory()->create([
            'subscription_status' => 'free',
            'videos_count' => 0,
        ]);

        $this->assertTrue($user->canRecordVideo());
    }

    public function test_free_user_cannot_record_after_limit()
    {
        $user = User::factory()->create([
            'subscription_status' => 'free',
        ]);

        // Create 1 video to reach the free tier limit
        \App\Models\Video::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->canRecordVideo());
    }

    public function test_active_user_can_always_record()
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
            'videos_count' => 100,
        ]);

        $this->assertTrue($user->canRecordVideo());
    }

    public function test_free_user_has_remaining_quota_of_one()
    {
        $user = User::factory()->create([
            'subscription_status' => 'free',
            'videos_count' => 0,
        ]);

        $this->assertEquals(1, $user->getRemainingVideoQuota());
    }

    public function test_free_user_has_zero_quota_after_one_video()
    {
        $user = User::factory()->create([
            'subscription_status' => 'free',
        ]);

        // Create 1 video to reach the free tier limit
        \App\Models\Video::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(0, $user->getRemainingVideoQuota());
    }

    public function test_active_user_has_unlimited_quota()
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
        ]);

        $this->assertNull($user->getRemainingVideoQuota());
    }

    public function test_canceled_user_in_grace_period()
    {
        $user = User::factory()->create([
            'subscription_status' => 'canceled',
            'subscription_expires_at' => now()->addDays(5),
        ]);

        $this->assertTrue($user->isSubscriptionInGracePeriod());
    }

    public function test_canceled_user_not_in_grace_period_after_expiry()
    {
        $user = User::factory()->create([
            'subscription_status' => 'canceled',
            'subscription_expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($user->isSubscriptionInGracePeriod());
    }

    public function test_active_user_not_in_grace_period()
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonth(),
        ]);

        $this->assertFalse($user->isSubscriptionInGracePeriod());
    }

    public function test_user_sensitive_fields_are_hidden()
    {
        $user = User::factory()->create([
            'polar_customer_id' => 'cust_secret_123',
            'polar_subscription_id' => 'sub_secret_456',
            'polar_product_id' => 'prod_secret_789',
            'polar_price_id' => 'price_secret_012',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('polar_customer_id', $array);
        $this->assertArrayNotHasKey('polar_subscription_id', $array);
        $this->assertArrayNotHasKey('polar_product_id', $array);
        $this->assertArrayNotHasKey('polar_price_id', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    public function test_user_subscription_dates_are_cast_to_datetime()
    {
        $user = User::factory()->create([
            'subscription_started_at' => '2025-01-01 12:00:00',
            'subscription_expires_at' => '2025-02-01 12:00:00',
            'subscription_canceled_at' => '2025-01-15 12:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->subscription_started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->subscription_expires_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->subscription_canceled_at);
    }

    public function test_videos_count_is_cast_to_integer()
    {
        $user = User::factory()->create([
            'videos_count' => '5',
        ]);

        $this->assertIsInt($user->videos_count);
        $this->assertEquals(5, $user->videos_count);
    }

    public function test_get_videos_count_returns_zero_when_not_set()
    {
        $user = User::factory()->create([
            'videos_count' => 0,
        ]);

        // Simulate null value in model (even though DB has default)
        $user->videos_count = null;

        $this->assertEquals(0, $user->getVideosCount());
    }
}
