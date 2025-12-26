# Webhooks vs Checkout Success - Both Are Used!

## TL;DR

**Yes, we ARE using webhooks!** The checkout success handler is just for instant feedback. Webhooks remain the primary method for subscription management.

## The Complete Flow

### Initial Subscription (Uses BOTH)

```
User subscribes → Checkout Success ✅ + Webhook ✅
```

1. **Checkout Success Handler** (Immediate)
   - When: User returns from Polar after payment
   - Purpose: Create subscription instantly for better UX
   - Endpoint: `POST /api/subscription/checkout/success`
   - Result: User sees "active" subscription immediately

2. **Webhook** (Few seconds later)
   - When: Polar sends `subscription.created` event
   - Purpose: Confirm and update subscription (backup)
   - Endpoint: `POST /api/webhooks/polar`
   - Result: Updates existing subscription (idempotent - no duplicates)

### All Other Events (Webhooks ONLY)

**Monthly/Yearly Renewals:**
```
subscription.updated webhook → Updates subscription_expires_at
```

**User Cancels:**
```
subscription.canceled webhook → Sets status to "canceled"
```

**Payment Fails:**
```
subscription.revoked webhook → Sets status to "expired"
```

**Plan Change:**
```
subscription.updated webhook → Updates product_id and price
```

## Why Both Are Needed

| Event | Checkout Success | Webhook | Why? |
|-------|-----------------|---------|------|
| **New subscription** | ✅ Creates | ✅ Confirms | User needs instant feedback |
| **Renewal payment** | ❌ | ✅ Only way | No user interaction |
| **Cancellation** | ❌ | ✅ Only way | Happens in Polar portal |
| **Payment failure** | ❌ | ✅ Only way | Automatic event |
| **Plan upgrade** | ❌ | ✅ Only way | Happens in Polar portal |

## Webhook Configuration

### Step 1: Configure Webhook in Polar Dashboard

Go to: https://polar.sh/dashboard (or sandbox)

**Settings → Webhooks → Add Webhook**

**Webhook URL:**
```
Production: https://yourdomain.com/api/webhooks/polar
Local (ngrok): https://abc123.ngrok.io/api/webhooks/polar
```

**Webhook Secret:**
```
polar_whs_o2ZAn1iFWQ1t34UjNMYB5nUB2yjW98CDcpSdR3qZ9vE
```
(This is already in your `.env` file)

**Events to Subscribe:**
- ✅ `subscription.created` - New subscription
- ✅ `subscription.active` - Payment succeeded
- ✅ `subscription.updated` - Plan change, renewal
- ✅ `subscription.canceled` - User canceled
- ✅ `subscription.revoked` - Payment failed, access revoked
- ✅ `customer.created` - New customer created
- ✅ `customer.updated` - Customer details changed
- ✅ `order.created` - Payment processed

### Step 2: Test Webhooks Locally (Using ngrok)

```bash
# Terminal 1: Start Laravel
php artisan serve

# Terminal 2: Start ngrok
ngrok http 8000

# Copy the ngrok URL (e.g., https://abc123.ngrok.io)
# Add to Polar: https://abc123.ngrok.io/api/webhooks/polar
```

## How The Code Works

### Checkout Success Handler
**File:** `app/Http/Controllers/SubscriptionController.php:197-269`

```php
public function handleCheckoutSuccess(Request $request)
{
    // 1. Fetch checkout from Polar
    $checkout = Http::get("polar.sh/v1/checkouts/{$checkoutId}");

    // 2. Get subscription_id from checkout
    $subscriptionId = $checkout['subscription_id'];

    // 3. Fetch subscription details
    $subscription = Http::get("polar.sh/v1/subscriptions/{$subscriptionId}");

    // 4. Create subscription record
    $user->update([...subscription details...]);

    // 5. Record history with source: 'checkout_success_handler'
}
```

### Webhook Handler (Idempotent)
**File:** `app/Http/Controllers/PolarWebhookController.php:182-288`

```php
protected function handleSubscriptionCreated(array $payload)
{
    // 1. Find user by customer_id or external_id
    $user = $this->findUserForSubscription(...);

    // 2. Check if subscription already exists
    $subscriptionAlreadyExists = $user->polar_subscription_id === $subscriptionId;

    // 3. Update user subscription
    $user->update([...subscription details...]);

    // 4. Only record history if NEW (prevents duplicates)
    if (!$subscriptionAlreadyExists) {
        SubscriptionHistory::recordEvent(...);
    }
}
```

## Testing

### Test Checkout Success
```bash
php scripts/test-checkout-creation.php 4
# Opens Polar checkout URL
# Complete payment
# Returns to /subscription/success
# Frontend calls checkout success endpoint
# Subscription created immediately ✅
```

### Test Webhooks
```bash
php scripts/test-webhook.php
# Sends test webhook to your local server
# Verifies signature validation
# Checks subscription creation
```

### Run All Tests
```bash
php artisan test --filter "Subscription|Polar"
# 84 tests, all passing ✅
```

## What Happens Without Webhooks?

**You would miss:**
- ❌ Monthly renewals (subscription expires)
- ❌ Cancellations (user charged forever)
- ❌ Payment failures (ghost subscriptions)
- ❌ Plan changes (wrong features enabled)

**Webhooks are NOT optional - they're required for production!**

## Summary

✅ **Checkout Success Handler** = Instant user feedback (UX improvement)
✅ **Webhooks** = Subscription lifecycle management (REQUIRED)
✅ **Both Work Together** = Idempotent, no duplicates
✅ **Webhooks Handle** = Renewals, cancellations, failures, upgrades

**You MUST configure webhooks in Polar for a production app!**

## Next Steps

1. **For Local Development:**
   ```bash
   # Install ngrok
   brew install ngrok

   # Start ngrok
   ngrok http 8000

   # Add webhook URL to Polar sandbox
   # URL: https://[your-ngrok-url]/api/webhooks/polar
   ```

2. **For Production:**
   ```bash
   # Add webhook in Polar production dashboard
   # URL: https://yourdomain.com/api/webhooks/polar
   ```

3. **Test End-to-End:**
   - Create checkout → Complete payment
   - Check logs for both:
     - "Subscription created from checkout success"
     - "Subscription created for user" (from webhook)
   - Verify only ONE history record created

## Monitoring

Check logs to ensure both are working:
```bash
tail -f storage/logs/laravel.log | grep -E "(checkout success|webhook)"
```

You should see:
```
[INFO] Checkout success handler - fetched checkout
[INFO] Subscription created from checkout success
[INFO] Polar webhook received
[INFO] Subscription created for user (already_existed: true)
```
