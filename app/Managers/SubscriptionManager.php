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
        // Get subscription from the package - this is the source of truth
        $subscription = $user->subscription();

        // Use package methods where available, fall back to User fields for backward compatibility
        return [
            'status' => $subscription ? $subscription->status->value : ($user->subscription_status ?? 'free'),
            'is_active' => $subscription ? $subscription->active() : $user->hasActiveSubscription(),
            'can_record' => $user->canRecordVideo(),
            'videos_count' => $user->getVideosCount(),
            'remaining_quota' => $user->getRemainingVideoQuota(),
            'started_at' => $subscription?->created_at ?? $user->subscription_started_at,
            'expires_at' => $subscription?->current_period_end ?? $user->subscription_expires_at,
            'canceled_at' => $subscription?->ends_at ?? $user->subscription_canceled_at,
            'is_in_grace_period' => $subscription ? $subscription->onGracePeriod() : $user->isSubscriptionInGracePeriod(),
            'on_trial' => $subscription ? $subscription->onTrial() : false,
            'trial_ends_at' => $subscription?->trial_ends_at,
        ];
    }

    public function getSubscriptionHistory(User $user): array
    {
        // Use the package's orders relationship instead of custom SubscriptionHistory
        return $user->orders()
            ->orderBy('ordered_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'event_type' => $order->billing_reason,
                    'event_label' => $this->getOrderEventLabel($order->billing_reason),
                    'status' => $order->status->value,
                    'period_start' => null, // Orders don't have period info
                    'period_end' => null,
                    'amount' => $order->amount,
                    'formatted_amount' => '$'.number_format($order->amount / 100, 2),
                    'currency' => $order->currency,
                    'plan_name' => null, // Would need to fetch product info
                    'plan_interval' => null,
                    'created_at' => $order->ordered_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get human-readable label for order billing reason
     */
    private function getOrderEventLabel(string $billingReason): string
    {
        return match ($billingReason) {
            'subscription_create' => 'Subscription Started',
            'subscription_cycle' => 'Subscription Renewed',
            'subscription_update' => 'Subscription Updated',
            default => ucfirst(str_replace('_', ' ', $billingReason)),
        };
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
