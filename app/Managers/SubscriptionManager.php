<?php

namespace App\Managers;

use App\Models\User;
use App\Repositories\SubscriptionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function createCheckout(User $user, string $plan = 'monthly'): array
    {
        $productId = $plan === 'yearly'
            ? config('services.polar.product_id_yearly')
            : config('services.polar.product_id_monthly');

        $frontendUrl = config('services.frontend.url');

        if (!$productId) {
            throw new \Exception("Polar product ID not configured for {$plan} plan");
        }

        $checkoutPayload = [
            'products' => [$productId],
            'success_url' => $frontendUrl . '/subscription/success?checkout_id={CHECKOUT_ID}',
            'customer_email' => $user->email,
            'customer_name' => $user->name,
            'customer_external_id' => (string) $user->id,
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan' => $plan,
            ],
        ];

        Log::info('Creating Polar checkout with user identification', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'customer_external_id' => (string) $user->id,
            'existing_polar_customer_id' => $user->polar_customer_id,
            'plan' => $plan,
        ]);

        if ($user->polar_customer_id && $this->isValidUuid($user->polar_customer_id)) {
            $checkoutPayload['customer_id'] = $user->polar_customer_id;
            unset($checkoutPayload['customer_email']);
            unset($checkoutPayload['customer_name']);

            Log::info('Using existing Polar customer ID', [
                'user_id' => $user->id,
                'polar_customer_id' => $user->polar_customer_id,
            ]);
        } elseif ($user->polar_customer_id) {
            Log::warning('Invalid polar_customer_id (not UUID), clearing it', [
                'user_id' => $user->id,
                'invalid_customer_id' => $user->polar_customer_id,
            ]);
            $this->subscriptions->clearCustomerId($user);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.polar.api_key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.polar.api_url') . '/v1/checkouts/', $checkoutPayload);

        if (!$response->successful()) {
            Log::error('Failed to create Polar checkout', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $checkoutPayload,
            ]);
            throw new \Exception('Failed to create checkout session');
        }

        $data = $response->json();

        Log::info('Polar checkout created', [
            'user_id' => $user->id,
            'checkout_id' => $data['id'] ?? null,
            'plan' => $plan,
        ]);

        return [
            'checkout_url' => $data['url'] ?? null,
            'checkout_id' => $data['id'] ?? null,
        ];
    }

    public function handleCheckoutSuccess(User $user, string $checkoutId): array
    {
        $checkoutResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.polar.api_key'),
            'Content-Type' => 'application/json',
        ])->get(config('services.polar.api_url') . '/v1/checkouts/' . $checkoutId);

        if (!$checkoutResponse->successful()) {
            Log::error('Failed to fetch checkout from Polar', [
                'checkout_id' => $checkoutId,
                'status' => $checkoutResponse->status(),
                'body' => $checkoutResponse->body(),
            ]);
            throw new \Exception('Failed to verify checkout');
        }

        $checkout = $checkoutResponse->json();
        $customerId = $checkout['customer_id'] ?? null;
        $subscriptionId = $checkout['subscription_id'] ?? null;

        Log::info('Checkout success handler - fetched checkout', [
            'user_id' => $user->id,
            'checkout_id' => $checkoutId,
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
            'status' => $checkout['status'] ?? null,
        ]);

        if ($customerId && !$user->polar_customer_id) {
            $this->subscriptions->updateUserSubscription($user, ['polar_customer_id' => $customerId]);
        }

        if ($subscriptionId) {
            $this->fetchAndCreateSubscription($user, $subscriptionId);
        }

        $user->refresh();

        return [
            'status' => $user->subscription_status ?? 'free',
            'is_active' => $user->hasActiveSubscription(),
            'started_at' => $user->subscription_started_at,
            'expires_at' => $user->subscription_expires_at,
        ];
    }

    public function fetchAndCreateSubscription(User $user, string $subscriptionId): void
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.polar.api_key'),
            'Content-Type' => 'application/json',
        ])->get(config('services.polar.api_url') . '/v1/subscriptions/' . $subscriptionId);

        if (!$response->successful()) {
            Log::error('Failed to fetch subscription from Polar', [
                'subscription_id' => $subscriptionId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return;
        }

        $subscription = $response->json();

        $periodEnd = isset($subscription['current_period_end'])
            ? Carbon::parse($subscription['current_period_end'])
            : null;

        $status = match ($subscription['status'] ?? null) {
            'active' => 'active',
            'canceled' => 'canceled',
            'incomplete', 'incomplete_expired' => 'incomplete',
            'trialing' => 'active',
            'past_due', 'unpaid' => 'expired',
            default => 'free',
        };

        $this->subscriptions->updateUserSubscription($user, [
            'polar_subscription_id' => $subscriptionId,
            'polar_customer_id' => $user->polar_customer_id ?? ($subscription['customer_id'] ?? null),
            'polar_product_id' => $subscription['product_id'] ?? null,
            'polar_price_id' => $subscription['price_id'] ?? null,
            'subscription_status' => $status,
            'subscription_started_at' => now(),
            'subscription_expires_at' => $periodEnd,
        ]);

        $this->subscriptions->recordSubscriptionEvent($user, 'created', $status, [
            'subscription_id' => $subscriptionId,
            'customer_id' => $subscription['customer_id'] ?? null,
            'product_id' => $subscription['product_id'] ?? null,
            'price_id' => $subscription['price_id'] ?? null,
            'period_start' => now(),
            'period_end' => $periodEnd,
            'amount' => $subscription['amount'] ?? null,
            'plan_interval' => $subscription['recurring_interval'] ?? null,
            'metadata' => [
                'source' => 'checkout_success_handler',
                'raw_status' => $subscription['status'] ?? null,
            ],
        ]);

        Log::info('Subscription created from checkout success', [
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'status' => $status,
        ]);
    }

    public function cancelSubscription(User $user): array
    {
        if (!$user->hasActiveSubscription()) {
            throw new \InvalidArgumentException('No active subscription to cancel');
        }

        if (!$user->polar_subscription_id) {
            throw new \InvalidArgumentException('Subscription ID not found');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.polar.api_key'),
            'Content-Type' => 'application/json',
        ])->delete(config('services.polar.api_url') . '/v1/subscriptions/' . $user->polar_subscription_id);

        if (!$response->successful()) {
            Log::error('Failed to cancel Polar subscription', [
                'subscription_id' => $user->polar_subscription_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to cancel subscription');
        }

        $this->subscriptions->updateUserSubscription($user, [
            'subscription_status' => 'canceled',
            'subscription_canceled_at' => now(),
        ]);

        return [
            'message' => 'Subscription canceled successfully',
            'expires_at' => $user->subscription_expires_at,
        ];
    }

    public function getPortalUrl(User $user): string
    {
        if (!$user->polar_customer_id) {
            throw new \InvalidArgumentException('Customer ID not found');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.polar.api_key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.polar.api_url') . '/v1/customer-portal/sessions', [
            'customer_id' => $user->polar_customer_id,
        ]);

        if (!$response->successful()) {
            Log::error('Failed to create customer portal session', [
                'customer_id' => $user->polar_customer_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to get portal URL');
        }

        $data = $response->json();

        return $data['url'] ?? '';
    }

    public function createPolarCustomer(User $user): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.polar.api_key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.polar.api_url') . '/v1/customers', [
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['id'] ?? null;
        }

        Log::error('Failed to create Polar customer', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    protected function isValidUuid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return (bool) preg_match($pattern, $uuid);
    }
}
