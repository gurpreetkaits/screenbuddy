# Subscription Checkout Flow

## Overview

The subscription system now ensures that subscriptions are **immediately created and stored** when a user completes checkout, without waiting for webhooks.

## The Problem (Before)

Previously, the subscription flow had a race condition:

1. User initiates checkout → Redirected to Polar
2. User completes payment → Redirected back to app at `/subscription/success?checkout_id={CHECKOUT_ID}`
3. **No endpoint handled this callback** → User sees success page but subscription not visible
4. System waits for webhook → Could take seconds or fail entirely
5. Subscription finally appears when webhook arrives

## The Solution (After)

Now subscriptions are created **proactively** when the user returns from checkout:

### Flow Diagram

```
User clicks "Subscribe"
    ↓
POST /api/subscription/checkout
    ↓
User redirected to Polar
    ↓
User completes payment
    ↓
Polar redirects to: /subscription/success?checkout_id={CHECKOUT_ID}
    ↓
Frontend calls: POST /api/subscription/checkout/success
    ↓
Backend:
  1. Fetches checkout from Polar API
  2. Extracts subscription_id
  3. Fetches subscription details
  4. Creates/updates user subscription record
  5. Records subscription history
    ↓
User immediately sees active subscription!
    ↓
(Later) Webhook arrives → Updates existing subscription (idempotent)
```

## Implementation Details

### New Endpoint: `POST /api/subscription/checkout/success`

**Location**: `SubscriptionController@handleCheckoutSuccess`

**Request**:
```json
{
  "checkout_id": "checkout_abc123"
}
```

**Response**:
```json
{
  "success": true,
  "subscription": {
    "status": "active",
    "is_active": true,
    "started_at": "2025-12-14T10:00:00Z",
    "expires_at": "2026-01-14T10:00:00Z"
  }
}
```

**What it does**:
1. Fetches checkout from Polar: `GET /v1/checkouts/{checkout_id}`
2. Extracts `customer_id` and `subscription_id`
3. Links customer to user if not already linked
4. If subscription exists, calls `fetchAndCreateSubscription()`
5. Fetches subscription: `GET /v1/subscriptions/{subscription_id}`
6. Updates user record with subscription details
7. Records subscription history with `source: checkout_success_handler`

### Idempotent Webhook Handling

**Updated**: `PolarWebhookController@handleSubscriptionCreated`

Now checks if subscription already exists:
```php
$subscriptionAlreadyExists = $user->polar_subscription_id === $subscriptionId;
```

- If subscription exists: Updates user, **does NOT create duplicate history**
- If subscription new: Updates user, creates history record
- Preserves original `subscription_started_at` if already set

This prevents duplicate history entries when both checkout handler and webhook process the same subscription.

## Database Structure

Subscriptions are stored in **two places**:

### 1. User Model (Current State)
```php
users table:
- polar_customer_id
- polar_subscription_id
- polar_product_id
- polar_price_id
- subscription_status (active, canceled, expired, free)
- subscription_started_at
- subscription_expires_at
- subscription_canceled_at
```

### 2. Subscription History (Event Log)
```php
subscription_history table:
- user_id
- event_type (created, activated, renewed, canceled, revoked)
- status
- polar_subscription_id
- polar_customer_id
- polar_product_id
- polar_price_id
- period_start
- period_end
- amount
- currency
- plan_name
- plan_interval
- metadata (includes 'source': webhook or checkout_success_handler)
```

## Frontend Integration

Update your frontend success page to call the new endpoint:

```javascript
// After redirect from Polar
const urlParams = new URLSearchParams(window.location.search);
const checkoutId = urlParams.get('checkout_id');

if (checkoutId) {
  const response = await fetch('/api/subscription/checkout/success', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ checkout_id: checkoutId })
  });

  const data = await response.json();

  if (data.success && data.subscription.is_active) {
    // Show success message
    // Redirect to dashboard or show active subscription UI
  }
}
```

## Testing

All tests pass (84 tests, 210 assertions):

```bash
php artisan test --filter "Subscription|Polar"
```

**Key tests**:
- `SubscriptionCheckoutSuccessTest` - New checkout success endpoint
- `PolarWebhookControllerTest` - Webhook handlers remain functional
- `test_webhook_does_not_duplicate_subscription_history` - Idempotency verified

## Benefits

1. **Instant feedback**: User sees subscription immediately after payment
2. **No race conditions**: Don't rely on webhook timing
3. **Redundancy**: Webhook still works as backup
4. **Idempotent**: Safe to process both checkout callback and webhook
5. **Better UX**: User doesn't see "free" tier after paying

## Troubleshooting

### User doesn't see subscription after checkout

1. Check frontend calls `/api/subscription/checkout/success`
2. Verify `checkout_id` is passed correctly
3. Check logs for API fetch errors:
   ```bash
   tail -f storage/logs/laravel.log | grep "checkout success"
   ```

### Duplicate subscription history entries

Should not happen - the webhook handler checks `subscriptionAlreadyExists`.
If it does occur, check the metadata:
```sql
SELECT user_id, event_type, created_at, metadata
FROM subscription_history
WHERE user_id = ?
ORDER BY created_at DESC;
```

Look for `source: checkout_success_handler` vs `source: webhook`.

## Security

- Endpoint requires authentication (`auth:sanctum` middleware)
- Only fetches checkout owned by authenticated user
- Uses Polar API with secure API key
- Validates checkout exists before processing
- No sensitive Polar IDs exposed in API responses
