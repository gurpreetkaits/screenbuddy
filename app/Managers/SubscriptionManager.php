<?php

namespace App\Managers;

use App\Models\User;
use App\Repositories\SubscriptionRepository;

class SubscriptionManager
{
    public function __construct(
        protected SubscriptionRepository $subscriptions
    ) {}

    public function getSubscriptionStatus(User $user): array
    {
        return [
            'status' => $user->subscription_status ?? 'free',
            'is_active' => $user->hasActiveSubscription(),
            'can_record' => $user->canRecordVideo(),
            'videos_count' => $user->getVideosCount(),
            'remaining_quota' => $user->getRemainingVideoQuota(),
            'started_at' => $user->subscription_started_at,
            'expires_at' => $user->subscription_expires_at,
            'canceled_at' => $user->subscription_canceled_at,
            'is_in_grace_period' => $user->isSubscriptionInGracePeriod(),
        ];
    }

    public function getSubscriptionHistory(User $user): array
    {
        return $this->subscriptions->getUserSubscriptionHistory($user)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'event_type' => $item->event_type,
                    'event_label' => $item->event_label,
                    'status' => $item->status,
                    'period_start' => $item->period_start,
                    'period_end' => $item->period_end,
                    'amount' => $item->amount,
                    'formatted_amount' => $item->formatted_amount,
                    'currency' => $item->currency,
                    'plan_name' => $item->plan_name,
                    'plan_interval' => $item->plan_interval,
                    'created_at' => $item->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Create a checkout session using the Polar package
     */
    public function createCheckout(User $user, string $plan = 'monthly'): array
    {
        $productId = $plan === 'yearly'
            ? config('services.polar.product_id_yearly')
            : config('services.polar.product_id_monthly');

        if (! $productId) {
            throw new \Exception("Polar product ID not configured for {$plan} plan");
        }

        $frontendUrl = config('services.frontend.url');

        // Use the Billable trait's checkout method and get URL
        $checkout = $user->checkout([$productId])
            ->withSuccessUrl($frontendUrl.'/subscription/success?checkout_id={CHECKOUT_ID}')
            ->url();

        return [
            'checkout_url' => $checkout,
            'checkout_id' => null, // Package doesn't expose this before URL generation
        ];
    }

    /**
     * Handle checkout success - the package handles this via webhooks automatically
     * This method is kept for backward compatibility
     */
    public function handleCheckoutSuccess(User $user, string $checkoutId): array
    {
        // The laravel-polar package handles subscription creation via webhooks automatically
        // We just need to refresh the user model to get the latest subscription data
        $user->refresh();

        return [
            'status' => $user->subscription_status ?? 'free',
            'is_active' => $user->hasActiveSubscription(),
            'started_at' => $user->subscription_started_at,
            'expires_at' => $user->subscription_expires_at,
        ];
    }

    /**
     * Cancel subscription using the package
     */
    public function cancelSubscription(User $user): array
    {
        if (! $user->hasActiveSubscription()) {
            throw new \InvalidArgumentException('No active subscription to cancel');
        }

        $subscription = $user->subscription();

        if (! $subscription) {
            throw new \InvalidArgumentException('User is not subscribed');
        }

        // Use the Billable trait's subscription cancellation
        $subscription->cancel();

        // Update user status manually since we're still using the old user fields
        $user->update([
            'subscription_status' => 'canceled',
            'subscription_canceled_at' => now(),
        ]);

        return [
            'message' => 'Subscription canceled successfully',
            'expires_at' => $user->subscription_expires_at,
        ];
    }

    /**
     * Get customer portal URL using the package
     */
    public function getPortalUrl(User $user): string
    {
        // Check if user has a customer record
        if (! $user->customer || ! $user->customer->polar_id) {
            throw new \InvalidArgumentException('Customer ID not found');
        }

        // Use the Billable trait's customer portal URL method
        return $user->customerPortalUrl();
    }
}
