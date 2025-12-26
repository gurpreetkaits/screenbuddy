<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * Get current user's subscription status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'subscription' => [
                'status' => $user->subscription_status ?? 'free',
                'is_active' => $user->hasActiveSubscription(),
                'can_record' => $user->canRecordVideo(),
                'videos_count' => $user->getVideosCount(),
                'remaining_quota' => $user->getRemainingVideoQuota(),
                'started_at' => $user->subscription_started_at,
                'expires_at' => $user->subscription_expires_at,
                'canceled_at' => $user->subscription_canceled_at,
                'is_in_grace_period' => $user->isSubscriptionInGracePeriod(),
            ],
        ]);
    }

    /**
     * Get user's subscription history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = $user->subscriptionHistory()
            ->select([
                'id',
                'event_type',
                'status',
                'period_start',
                'period_end',
                'amount',
                'currency',
                'plan_name',
                'plan_interval',
                'created_at',
            ])
            ->limit(50)
            ->get()
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
            });

        return response()->json([
            'history' => $history,
        ]);
    }

    /**
     * Create a Polar checkout session for subscription
     *
     * @see https://polar.sh/docs/features/checkout/session
     */
    public function createCheckout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate plan selection
        $request->validate([
            'plan' => 'sometimes|in:monthly,yearly',
        ]);

        $plan = $request->input('plan', 'monthly');

        try {
            // Get the appropriate product ID based on plan
            $productId = $plan === 'yearly'
                ? config('services.polar.product_id_yearly')
                : config('services.polar.product_id_monthly');

            $frontendUrl = config('services.frontend.url');

            if (!$productId) {
                return response()->json([
                    'error' => "Polar product ID not configured for {$plan} plan",
                ], 500);
            }

            // Build checkout payload per Polar API spec
            $checkoutPayload = [
                'products' => [$productId],
                'success_url' => $frontendUrl . '/subscription/success?checkout_id={CHECKOUT_ID}',
                'customer_email' => $user->email,
                'customer_name' => $user->name,
                'customer_external_id' => (string) $user->id, // Maps Polar customer to our user
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'plan' => $plan,
                ],
            ];

            // DEBUG: Log user identification for debugging webhook issues
            Log::info('Creating Polar checkout with user identification', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'customer_external_id' => (string) $user->id,
                'existing_polar_customer_id' => $user->polar_customer_id,
                'plan' => $plan,
            ]);

            // If user already has a Polar customer ID, validate and use it
            if ($user->polar_customer_id) {
                // Validate that customer_id is a valid UUID (Polar's requirement)
                if ($this->isValidUuid($user->polar_customer_id)) {
                    $checkoutPayload['customer_id'] = $user->polar_customer_id;
                    unset($checkoutPayload['customer_email']);
                    unset($checkoutPayload['customer_name']);

                    Log::info('Using existing Polar customer ID', [
                        'user_id' => $user->id,
                        'polar_customer_id' => $user->polar_customer_id,
                    ]);
                } else {
                    // Invalid UUID - clear it and use customer details instead
                    Log::warning('Invalid polar_customer_id (not UUID), clearing it', [
                        'user_id' => $user->id,
                        'invalid_customer_id' => $user->polar_customer_id,
                    ]);
                    $user->update(['polar_customer_id' => null]);
                }
            }

            // Create checkout session via Polar API
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
                return response()->json([
                    'error' => 'Failed to create checkout session',
                ], 500);
            }

            $data = $response->json();

            Log::info('Polar checkout created', [
                'user_id' => $user->id,
                'checkout_id' => $data['id'] ?? null,
                'plan' => $plan,
            ]);

            return response()->json([
                'checkout_url' => $data['url'] ?? null,
                'checkout_id' => $data['id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Polar checkout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while creating checkout session',
            ], 500);
        }
    }

    /**
     * Get checkout URL for embedding
     */
    public function getCheckoutUrl(Request $request): JsonResponse
    {
        // Re-use createCheckout logic
        return $this->createCheckout($request);
    }

    /**
     * Handle successful checkout completion
     * Called when user returns from Polar after payment
     * Proactively fetches subscription to avoid waiting for webhook
     */
    public function handleCheckoutSuccess(Request $request): JsonResponse
    {
        $user = $request->user();
        $checkoutId = $request->input('checkout_id');

        if (!$checkoutId) {
            return response()->json([
                'error' => 'Missing checkout_id parameter',
            ], 400);
        }

        try {
            // Fetch checkout details from Polar
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
                return response()->json([
                    'error' => 'Failed to verify checkout',
                ], 500);
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

            // Link customer ID to user if not already set
            if ($customerId && !$user->polar_customer_id) {
                $user->update(['polar_customer_id' => $customerId]);
            }

            // If subscription ID exists, fetch and create subscription
            if ($subscriptionId) {
                $this->fetchAndCreateSubscription($user, $subscriptionId);
            }

            // Return updated subscription status
            return response()->json([
                'success' => true,
                'subscription' => [
                    'status' => $user->fresh()->subscription_status ?? 'free',
                    'is_active' => $user->hasActiveSubscription(),
                    'started_at' => $user->subscription_started_at,
                    'expires_at' => $user->subscription_expires_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling checkout success', [
                'checkout_id' => $checkoutId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while processing checkout',
            ], 500);
        }
    }

    /**
     * Fetch subscription from Polar API and create/update local record
     */
    protected function fetchAndCreateSubscription($user, string $subscriptionId): void
    {
        try {
            // Fetch subscription details from Polar
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
                ? \Carbon\Carbon::parse($subscription['current_period_end'])
                : null;

            // Map Polar status to our internal status
            $status = match ($subscription['status'] ?? null) {
                'active' => 'active',
                'canceled' => 'canceled',
                'incomplete', 'incomplete_expired' => 'incomplete',
                'trialing' => 'active',
                'past_due', 'unpaid' => 'expired',
                default => 'free',
            };

            // Update user subscription
            $user->update([
                'polar_subscription_id' => $subscriptionId,
                'polar_customer_id' => $user->polar_customer_id ?? ($subscription['customer_id'] ?? null),
                'polar_product_id' => $subscription['product_id'] ?? null,
                'polar_price_id' => $subscription['price_id'] ?? null,
                'subscription_status' => $status,
                'subscription_started_at' => now(),
                'subscription_expires_at' => $periodEnd,
            ]);

            // Record subscription history
            \App\Models\SubscriptionHistory::recordEvent($user, 'created', $status, [
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
        } catch (\Exception $e) {
            Log::error('Error fetching/creating subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel user's subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasActiveSubscription()) {
            return response()->json([
                'error' => 'No active subscription to cancel',
            ], 400);
        }

        if (!$user->polar_subscription_id) {
            return response()->json([
                'error' => 'Subscription ID not found',
            ], 400);
        }

        try {
            // Cancel subscription via Polar API
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
                return response()->json([
                    'error' => 'Failed to cancel subscription',
                ], 500);
            }

            // Update local status (webhook will also update this)
            $user->update([
                'subscription_status' => 'canceled',
                'subscription_canceled_at' => now(),
            ]);

            return response()->json([
                'message' => 'Subscription canceled successfully',
                'expires_at' => $user->subscription_expires_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Error canceling subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while canceling subscription',
            ], 500);
        }
    }

    /**
     * Get Polar customer portal URL
     */
    public function getPortalUrl(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->polar_customer_id) {
            return response()->json([
                'error' => 'Customer ID not found',
            ], 400);
        }

        try {
            // Get customer portal session from Polar
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
                return response()->json([
                    'error' => 'Failed to get portal URL',
                ], 500);
            }

            $data = $response->json();

            return response()->json([
                'portal_url' => $data['url'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting portal URL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while getting portal URL',
            ], 500);
        }
    }

    /**
     * Validate if a string is a valid UUID
     */
    protected function isValidUuid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return (bool) preg_match($pattern, $uuid);
    }

    /**
     * Create a Polar customer for the user
     */
    protected function createPolarCustomer($user): ?string
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error creating Polar customer', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
