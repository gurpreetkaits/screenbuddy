<?php

namespace App\Providers;

use Danestves\LaravelPolar\Events\SubscriptionActive;
use Danestves\LaravelPolar\Events\SubscriptionCanceled;
use Danestves\LaravelPolar\Events\SubscriptionCreated;
use Danestves\LaravelPolar\Events\SubscriptionRevoked;
use Danestves\LaravelPolar\Events\WebhookReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Log incoming Polar webhooks
        Event::listen(WebhookReceived::class, function ($event) {
            Log::channel('daily')->info('Polar webhook received', [
                'type' => $event->payload['type'] ?? 'unknown',
                'payload' => $event->payload,
            ]);
        });

        // Sync User model when subscription is created
        Event::listen(SubscriptionCreated::class, function ($event) {
            Log::channel('daily')->info('Polar subscription created', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
                'status' => $event->subscription->status->value ?? null,
            ]);

            // Update User model with subscription data
            $user = $event->billable;
            $subscription = $event->subscription;

            $user->update([
                'subscription_status' => $subscription->status->value,
                'polar_subscription_id' => $subscription->polar_id,
                'polar_product_id' => $subscription->product_id,
                'subscription_started_at' => now(),
                'subscription_expires_at' => $subscription->current_period_end,
            ]);

            Log::channel('daily')->info('User subscription status updated', [
                'user_id' => $user->id,
                'status' => $subscription->status->value,
            ]);
        });

        // Sync User model when subscription becomes active
        Event::listen(SubscriptionActive::class, function ($event) {
            Log::channel('daily')->info('Polar subscription activated', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
            ]);

            $user = $event->billable;
            $subscription = $event->subscription;

            $user->update([
                'subscription_status' => 'active',
                'subscription_expires_at' => $subscription->current_period_end,
            ]);
        });

        // Sync User model when subscription is canceled
        Event::listen(SubscriptionCanceled::class, function ($event) {
            Log::channel('daily')->info('Polar subscription canceled', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
            ]);

            $user = $event->billable;
            $subscription = $event->subscription;

            $user->update([
                'subscription_status' => 'canceled',
                'subscription_canceled_at' => now(),
                'subscription_expires_at' => $subscription->ends_at ?? $subscription->current_period_end,
            ]);
        });

        // Sync User model when subscription is revoked (ended)
        Event::listen(SubscriptionRevoked::class, function ($event) {
            Log::channel('daily')->info('Polar subscription revoked', [
                'billable_id' => $event->billable->id ?? null,
                'subscription_id' => $event->subscription->polar_id ?? null,
            ]);

            $user = $event->billable;

            $user->update([
                'subscription_status' => 'expired',
                'subscription_expires_at' => now(),
            ]);
        });
    }
}
