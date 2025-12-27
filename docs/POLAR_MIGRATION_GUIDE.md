# Polar Integration Migration Guide

This guide documents the migration from custom Polar integration to the official `danestves/laravel-polar` package.

## Summary of Changes

### 1. PHP Version Upgrade
- **Upgraded from:** PHP 8.2
- **Upgraded to:** PHP 8.3
- **Reason:** The `laravel-polar` package requires PHP 8.3+

### 2. Package Installation
- **Installed:** `danestves/laravel-polar` v2.0.3
- **Dependencies:** Includes `polar-sh/sdk`, `spatie/laravel-data`, `spatie/laravel-webhook-client`

### 3. Environment Variables
Updated `.env.example` with new package-compatible variable names:

**Old Variables:**
```env
POLAR_API_KEY=
POLAR_ENVIRONMENT=sandbox
```

**New Variables:**
```env
POLAR_ACCESS_TOKEN=
POLAR_SERVER=sandbox
POLAR_WEBHOOK_SECRET=
POLAR_PATH=polar
POLAR_CURRENCY_LOCALE=en
```

**Note:** The old variables are still supported via `config/services.php` for backward compatibility.

### 4. Database Changes
The package introduces new tables:
- `polar_customers` - Manages customer relationships (polymorphic)
- `polar_orders` - Stores order data
- `polar_subscriptions` - Manages subscription data

**Migration Status:** Pending - Run when database is available:
```bash
php artisan migrate
```

### 5. Code Refactoring

#### User Model (`app/Models/User.php`)
- **Added:** `Danestves\LaravelPolar\Billable` trait
- **Provides:** Simplified subscription management methods

#### SubscriptionManager (`app/Managers/SubscriptionManager.php`)
**Simplified methods:**
- `createCheckout()` - Now uses `$user->checkout()`
- `cancelSubscription()` - Now uses `$user->subscription()->cancel()`
- `getPortalUrl()` - Now uses `$user->customerPortalUrl()`
- **Removed:** Manual HTTP API calls, manual webhook signature verification
- **Lines of code:** Reduced from ~370 to ~126 (66% reduction)

#### Webhook Handling
- **Old Route:** `POST /api/webhooks/polar` (deprecated)
- **New Route:** `POST /polar/webhook` (automatically registered by package)
- **Change:** Custom `PolarWebhookController` replaced with package's automatic handling

## Migration Steps

### Step 1: Update Your .env File
Copy the new environment variables from `.env.example`:
```env
POLAR_ACCESS_TOKEN=your_polar_api_key_here
POLAR_SERVER=sandbox  # or "production"
POLAR_WEBHOOK_SECRET=your_webhook_secret_here
POLAR_PATH=polar
POLAR_CURRENCY_LOCALE=en
```

### Step 2: Run Database Migrations
When your database is running:
```bash
php artisan migrate
```

### Step 3: Update Polar Dashboard Webhook URL
1. Log in to [Polar Dashboard](https://polar.sh)
2. Go to **Settings > Webhooks**
3. Update your webhook URL to: `https://yourdomain.com/polar/webhook`
4. Ensure the webhook secret matches your `POLAR_WEBHOOK_SECRET` env variable

### Step 4: Test the Integration

#### Test Checkout Creation
```bash
php artisan tinker
```
```php
$user = User::first();
$checkout = $user->checkout(['your_product_id']);
echo $checkout->url; // Should return a Polar checkout URL
```

#### Test Subscription Status
```php
$user = User::first();
$user->subscribed(); // Returns boolean
$user->subscription(); // Returns subscription instance
```

#### Test Customer Portal
```php
$user = User::first();
$url = $user->customerPortalUrl();
echo $url; // Should return customer portal URL
```

## Package Features You Can Now Use

### Checkout Creation
```php
// Simple product checkout
$checkout = $user->checkout(['product_id']);

// Multiple products
$checkout = $user->checkout(['product_id_1', 'product_id_2']);

// With custom success URL
$checkout = $user->checkout(
    products: ['product_id'],
    successUrl: 'https://yourdomain.com/success?checkout_id={CHECKOUT_ID}'
);
```

### Subscription Management
```php
// Create subscription
$user->subscribe('product_id');

// Check if subscribed
if ($user->subscribed()) { /* ... */ }

// Get subscription
$subscription = $user->subscription();

// Check subscription status
$subscription->valid();      // Active and not past due
$subscription->cancelled();   // Was cancelled
$subscription->onGracePeriod(); // Cancelled but still active
$subscription->pastDue();    // Payment failed

// Swap plans
$subscription->swap('new_product_id');

// Cancel subscription
$subscription->cancel();
```

### Customer Portal
```php
// Get portal URL
$url = $user->customerPortalUrl();

// Redirect to portal
return $user->redirectToCustomerPortal();
```

### Orders
```php
// Access user's orders
$orders = $user->orders;

// Check if purchased product
if ($user->hasPurchasedProduct('product_id')) { /* ... */ }
```

## Custom Webhook Listeners (Optional)

If you need custom business logic when webhooks arrive, create Laravel event listeners:

```bash
php artisan make:listener HandleSubscriptionCreated
```

```php
namespace App\Listeners;

use Danestves\LaravelPolar\Events\SubscriptionCreated;

class HandleSubscriptionCreated
{
    public function handle(SubscriptionCreated $event)
    {
        $subscription = $event->subscription;
        $customer = $event->customer;

        // Your custom logic here
        // e.g., send welcome email, update analytics, etc.
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    \Danestves\LaravelPolar\Events\SubscriptionCreated::class => [
        \App\Listeners\HandleSubscriptionCreated::class,
    ],
];
```

## Files That Can Be Removed (Optional)

After confirming the migration works, you can optionally remove these files:

- `app/Http/Controllers/PolarWebhookController.php` - Replaced by package
- `scripts/test-webhook*.php` - Test scripts for old implementation
- `scripts/capture-webhook-payload.php` - No longer needed

**Note:** Keep `app/Services/ApiLogger.php` if used elsewhere in the codebase.

## Backward Compatibility Notes

The following are maintained for backward compatibility:
- User model subscription fields (`polar_customer_id`, `polar_subscription_id`, etc.)
- `SubscriptionHistory` model and records
- `config/services.php` accepts both old and new environment variable names

## Architecture Changes

### Old Architecture
```
User Table
├── polar_customer_id
├── polar_subscription_id
├── subscription_status
├── subscription_started_at
└── subscription_expires_at

Custom API Calls → Polar API
Custom Webhook Handler → Manual Processing
```

### New Architecture (Package)
```
User Table (Billable)
└── Uses Billable Trait

polar_customers Table (Polymorphic)
├── billable_type
├── billable_id
└── polar_id

polar_subscriptions Table
├── subscription data
└── automatic sync via webhooks

polar_orders Table
└── order history

Package Methods → Polar SDK → Polar API
Package Webhook Route → Automatic Processing → Events
```

## Testing Checklist

- [ ] Run `php artisan migrate` successfully
- [ ] Update `.env` with new variable names
- [ ] Update Polar webhook URL in dashboard
- [ ] Test checkout creation via SubscriptionManager
- [ ] Test subscription cancellation
- [ ] Test customer portal URL generation
- [ ] Verify webhook events are received at new endpoint
- [ ] Confirm subscription status updates via webhooks
- [ ] Check that old subscription data migrated correctly

## Troubleshooting

### Issue: Migrations fail
**Solution:** Ensure database is running and `.env` has correct database credentials

### Issue: Checkout creation fails
**Solution:**
- Verify `POLAR_ACCESS_TOKEN` is set correctly
- Check product IDs are valid in your Polar account
- Ensure `POLAR_SERVER` matches your Polar environment

### Issue: Webhooks not received
**Solution:**
- Verify webhook URL in Polar dashboard is correct: `https://yourdomain.com/polar/webhook`
- Ensure `POLAR_WEBHOOK_SECRET` matches the secret in Polar dashboard
- Check that the route is not blocked by middleware or firewall

### Issue: "Call to undefined method subscription()"
**Solution:** Ensure:
- `Billable` trait is added to User model
- Package migrations have been run
- User has a customer record in `polar_customers` table

## Support Resources

- **Package Documentation:** [GitHub - danestves/laravel-polar](https://github.com/danestves/laravel-polar)
- **Polar API Docs:** [docs.polar.sh](https://docs.polar.sh)
- **Laravel Polar Guide:** [docs.polar.sh/integrate/sdk/adapters/laravel](https://docs.polar.sh/integrate/sdk/adapters/laravel)

## Next Steps

1. ✅ PHP upgraded to 8.3
2. ✅ Package installed and configured
3. ✅ User model updated with Billable trait
4. ✅ SubscriptionManager refactored
5. ✅ Webhook handling updated
6. ⏳ Run migrations when database is available
7. ⏳ Update production `.env` file
8. ⏳ Update Polar dashboard webhook URL
9. ⏳ Test integration in sandbox environment
10. ⏳ Deploy and test in production

---

**Migration completed:** 2025-12-27
**Package version:** danestves/laravel-polar v2.0.3
**PHP version:** 8.3.29
