# Polar Subscription Integration - ScreenSense

## Overview

This document describes the complete Polar.sh subscription integration for ScreenSense, implementing a Free vs Pro subscription model:

- **Free Tier**: Limited to 1 video recording
- **Pro Tier**: Unlimited video recordings ($7/month or $80/year)
- **Checkout**: Embedded Polar widget for seamless payment experience
- **Security**: Server-side subscription validation with webhook verification

---

## Prerequisites

### 1. Polar Account Setup

1. Create a Polar account at https://polar.sh
2. Create an organization (if you haven't already)
3. Create a product for "ScreenSense Pro" subscription:
   - Go to Products → Create Product
   - Set product type to "Subscription"
   - Configure pricing (monthly/yearly)
   - Note down the Product ID

### 2. Polar API Configuration

1. Navigate to Settings → API Keys
2. Create a new API key with the following permissions:
   - `products:read`
   - `customers:write`
   - `subscriptions:read`
   - `subscriptions:write`
   - `checkouts:write`
3. Copy the API key (starts with `polar_sk_`)
4. Copy your Organization ID from the organization settings

### 3. Webhook Setup

1. Go to Settings → Webhooks → Create Endpoint
2. Set the webhook URL to: `https://yourdomain.com/api/webhooks/polar`
3. Select the following events:
   - `subscription.created`
   - `subscription.updated`
   - `subscription.canceled`
   - `subscription.revoked`
   - `order.created`
   - `customer.updated`
4. Copy the Webhook Secret (starts with `whsec_`)

---

## Installation

### Backend Setup

#### 1. Environment Variables

Add the following to your `.env` file:

```bash
# Polar.sh Subscription Configuration
POLAR_API_KEY=polar_sk_xxxxxxxxxxxxx
POLAR_ORGANIZATION_ID=org_xxxxxxxxxxxxx
POLAR_PRODUCT_ID_MONTHLY=prod_xxxxxxxxxxxxx   # $7/month product
POLAR_PRODUCT_ID_YEARLY=prod_xxxxxxxxxxxxx    # $80/year product
POLAR_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
POLAR_ENVIRONMENT=production
```

For testing, you can use `POLAR_ENVIRONMENT=sandbox` to use Polar's sandbox environment.

#### 2. Database Migration

The migration has already been run, adding the following fields to the `users` table:

- `polar_customer_id` - Unique Polar customer identifier
- `polar_subscription_id` - Active subscription ID
- `subscription_status` - Enum: free, active, canceled, expired, incomplete
- `subscription_started_at` - When subscription started
- `subscription_expires_at` - When subscription ends/renews
- `subscription_canceled_at` - When user canceled (null if not canceled)
- `polar_product_id` - Subscribed product ID
- `polar_price_id` - Price tier ID
- `videos_count` - Cached count for performance

#### 3. Configuration

All Polar settings are configured in `config/services.php`:

```php
'polar' => [
    'api_key' => env('POLAR_API_KEY'),
    'organization_id' => env('POLAR_ORGANIZATION_ID'),
    'product_id_monthly' => env('POLAR_PRODUCT_ID_MONTHLY'),
    'product_id_yearly' => env('POLAR_PRODUCT_ID_YEARLY'),
    'webhook_secret' => env('POLAR_WEBHOOK_SECRET'),
    'environment' => env('POLAR_ENVIRONMENT', 'production'),
    'api_url' => env('POLAR_ENVIRONMENT', 'production') === 'production'
        ? 'https://api.polar.sh'
        : 'https://sandbox-api.polar.sh',
],
```

### Frontend Setup

#### 1. Install Dependencies

```bash
cd frontend
npm install @polar-sh/checkout
```

#### 2. Auth Store

The auth store (`frontend/src/stores/auth.js`) has been updated with subscription management:

- `fetchSubscription()` - Fetch current subscription status
- `canRecordVideo` - Computed property for recording permission
- `getRemainingQuota` - Computed property for remaining quota
- `hasActiveSubscription` - Computed property for active status

---

## Architecture

### Backend Components

#### 1. PolarWebhookController (`app/Http/Controllers/PolarWebhookController.php`)

Handles all webhook events from Polar:

**Events Handled:**
- `subscription.created` - New subscription started
- `subscription.updated` - Subscription modified or renewed
- `subscription.canceled` - User canceled (grace period starts)
- `subscription.revoked` - Subscription terminated (payment failed)
- `order.created` - Payment successful
- `customer.updated` - Customer info changed

**Security:**
- Implements Standard Webhooks signature verification
- Validates timestamp to prevent replay attacks (5-minute window)
- Rate limited to 60 requests/minute
- Logs all webhook events for audit

#### 2. SubscriptionController (`app/Http/Controllers/SubscriptionController.php`)

API endpoints for subscription management:

- `GET /api/subscription/status` - Current subscription details
- `POST /api/subscription/checkout` - Create checkout session
- `GET /api/subscription/checkout-url` - Get checkout URL for embedding
- `POST /api/subscription/cancel` - Cancel active subscription
- `GET /api/subscription/portal` - Get Polar customer portal URL

#### 3. CheckSubscriptionLimit Middleware (`app/Http/Middleware/CheckSubscriptionLimit.php`)

Enforces video recording limits:
- Checks if user can record based on subscription status
- Returns 403 error with upgrade URL if limit reached
- Applied to video upload endpoints

#### 4. User Model Methods (`app/Models/User.php`)

Subscription helper methods:
- `hasActiveSubscription()` - Check if user has paid subscription
- `canRecordVideo()` - Check if user can record more videos
- `getRemainingVideoQuota()` - Get remaining quota (null = unlimited)
- `isSubscriptionInGracePeriod()` - Check if canceled but still active
- `getVideosCount()` - Get cached video count
- `syncVideosCount()` - Recalculate video count from database

### Frontend Components

#### Auth Store (`frontend/src/stores/auth.js`)

**State:**
- `subscription` - Subscription data from API

**Methods:**
- `fetchSubscription()` - Fetch from backend
- `canRecordVideo` - Computed boolean
- `getRemainingQuota` - Computed number/null
- `hasActiveSubscription` - Computed boolean

---

## API Reference

### Subscription Endpoints

All subscription endpoints require authentication (`Authorization: Bearer {token}`).

#### Get Subscription Status

```http
GET /api/subscription/status
```

**Response:**
```json
{
  "subscription": {
    "status": "active",
    "is_active": true,
    "can_record": true,
    "videos_count": 5,
    "remaining_quota": null,
    "started_at": "2025-01-01T00:00:00Z",
    "expires_at": "2025-02-01T00:00:00Z",
    "canceled_at": null,
    "is_in_grace_period": false
  }
}
```

#### Create Checkout Session

```http
POST /api/subscription/checkout
```

**Response:**
```json
{
  "checkout_url": "https://polar.sh/checkout/...",
  "checkout_id": "checkout_..."
}
```

#### Cancel Subscription

```http
POST /api/subscription/cancel
```

**Response:**
```json
{
  "message": "Subscription canceled successfully",
  "expires_at": "2025-02-01T00:00:00Z"
}
```

### Video Upload with Limit Check

```http
POST /api/videos
```

**Free user at limit - Error Response (403):**
```json
{
  "error": "video_limit_reached",
  "message": "You have reached your video limit. Upgrade to Pro to continue recording.",
  "videos_count": 1,
  "remaining_quota": 0,
  "upgrade_url": "http://localhost:5173/subscription"
}
```

---

## Testing Guide

### Local Webhook Testing with ngrok

Since Polar needs to send webhooks to your local development server, use ngrok for tunneling:

#### 1. Install ngrok

```bash
brew install ngrok  # macOS
# or download from https://ngrok.com
```

#### 2. Start ngrok tunnel

```bash
ngrok http 8000
```

Copy the HTTPS URL (e.g., `https://abc123.ngrok.io`)

#### 3. Update Polar Webhook URL

In Polar dashboard:
1. Go to Settings → Webhooks
2. Edit your webhook endpoint
3. Set URL to: `https://abc123.ngrok.io/api/webhooks/polar`
4. Save

#### 4. Test Webhook Events

Create a test subscription in Polar dashboard to trigger webhook events. Check your Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

### Testing Subscription Flow

#### 1. Create Test User

Register or login to ScreenSense with a test account.

#### 2. Test Free Tier Limit

1. Record and upload 1 video
2. Try to record a 2nd video
3. Should see "Upgrade to Pro" message
4. Frontend should block recording attempt
5. Backend returns 403 error

#### 3. Test Upgrade Flow

1. Click "Upgrade" button
2. Checkout modal appears with embedded Polar checkout
3. Complete payment (use Polar test cards if in sandbox)
4. Webhook received by backend
5. User subscription status updated to "active"
6. Can now record unlimited videos

#### 4. Test Cancellation

1. Navigate to subscription settings
2. Click "Cancel Subscription"
3. Confirm cancellation
4. Status changes to "canceled"
5. Can still record videos until `expires_at` date
6. After expiration, reverts to free tier (1 video limit)

### Polar Test Cards (Sandbox Mode)

When using `POLAR_ENVIRONMENT=sandbox`, use these test cards:

- **Successful Payment**: `4242 4242 4242 4242`
- **Failed Payment**: `4000 0000 0000 0002`
- **Requires 3DS**: `4000 0027 6000 3184`

Expiry: Any future date (e.g., `12/30`)
CVC: Any 3 digits (e.g., `123`)

---

## Webhook Events Reference

### subscription.created

Triggered when a new subscription is created.

**Payload:**
```json
{
  "type": "subscription.created",
  "data": {
    "id": "sub_xxx",
    "customer_id": "cus_xxx",
    "product_id": "prod_xxx",
    "price_id": "price_xxx",
    "status": "active",
    "current_period_end": "2025-02-01T00:00:00Z"
  }
}
```

**Action:** Set user subscription to active, record start date and expiration.

### subscription.updated

Triggered on subscription renewal or modification.

**Action:** Update subscription status and expiration date.

### subscription.canceled

Triggered when user cancels subscription.

**Action:** Mark as canceled, set `canceled_at`, keep access until `expires_at`.

### subscription.revoked

Triggered when subscription is terminated (payment failure, admin action).

**Action:** Immediately set status to "expired" and block access.

---

## Security Best Practices

### 1. Webhook Verification

All webhooks are verified using Standard Webhooks specification:

- Signature verification using HMAC-SHA256
- Timestamp validation (5-minute tolerance)
- Multiple signature versions supported

Never skip webhook verification in production.

### 2. Server-Side Validation

- **Never** trust frontend subscription status
- Always check `User::canRecordVideo()` on backend
- Use middleware AND controller checks (defense in depth)
- Use database transactions to prevent race conditions

### 3. Environment Variables

- Store all Polar secrets in `.env`
- Never commit `.env` to version control
- Use different credentials for sandbox vs production
- Rotate webhook secret if compromised

### 4. Rate Limiting

Webhook endpoint is rate limited to 60 requests/minute to prevent abuse.

---

## Deployment Checklist

Before deploying to production:

- [ ] Set all Polar environment variables in `.env`
- [ ] Update webhook URL in Polar dashboard to production domain
- [ ] Verify SSL certificate is valid (webhooks require HTTPS)
- [ ] Test webhook delivery with Polar's webhook testing tool
- [ ] Enable rate limiting on `/api/webhooks/polar`
- [ ] Set up monitoring/alerts for failed webhooks
- [ ] Test complete subscription flow end-to-end
- [ ] Verify subscription status syncs correctly
- [ ] Test cancellation and grace period behavior
- [ ] Set up scheduled job to check expired subscriptions

---

## Troubleshooting

### Webhook Signature Verification Fails

**Symptoms:** Webhooks return 401 Unauthorized

**Solutions:**
1. Check `POLAR_WEBHOOK_SECRET` is correct in `.env`
2. Verify webhook secret is base64 encoded in Polar dashboard
3. Check server time is synchronized (NTP)
4. Ensure request body is not modified before verification
5. Check Laravel logs for detailed error message

### User Shows "Paid" but Can't Record

**Symptoms:** Backend thinks user is free tier despite payment

**Solutions:**
1. Check webhook was received (check `storage/logs/laravel.log`)
2. Verify `polar_customer_id` matches between user and Polar
3. Run `php artisan tinker` and check user subscription status:
   ```php
   $user = User::find(1);
   $user->subscription_status;
   $user->hasActiveSubscription();
   ```
4. Manually trigger webhook replay in Polar dashboard

### Video Count Out of Sync

**Symptoms:** `videos_count` doesn't match actual count

**Solutions:**
1. Run sync job:
   ```php
   $user->syncVideosCount();
   ```
2. Check if video deletions are decrementing count properly
3. Verify uploads are incrementing count

### Checkout URL Not Working

**Symptoms:** Checkout URL returns error or 404

**Solutions:**
1. Verify `POLAR_API_KEY` has `checkouts:write` permission
2. Check `POLAR_PRODUCT_ID` is correct
3. Ensure product is published in Polar dashboard
4. Verify API URL (production vs sandbox)
5. Check Laravel logs for API response details

---

## Monitoring

### Key Metrics to Track

1. **Subscription Conversions**
   - Free → Paid conversion rate
   - Time to first subscription
   - Checkout abandonment rate

2. **Webhook Health**
   - Webhook delivery success rate
   - Average webhook processing time
   - Failed webhook retries

3. **Video Usage**
   - Free tier video uploads (should max at 1)
   - Pro tier average videos per user
   - Videos deleted after upgrade

4. **Churn**
   - Cancellation rate
   - Re-subscription rate during grace period
   - Reasons for cancellation (if collected)

### Logging

All subscription events are logged to `storage/logs/laravel.log`:

```bash
# Watch subscription logs
tail -f storage/logs/laravel.log | grep -i "subscription\|polar\|webhook"
```

Key log entries:
- `Polar webhook received` - Every webhook
- `Subscription created for user` - New subscriptions
- `Subscription canceled for user` - Cancellations
- `Polar webhook signature verification failed` - Security issues

---

## Future Enhancements

### Potential Features

1. **Multiple Tiers**
   - Basic: 5 videos/month
   - Pro: Unlimited videos
   - Team: Shared quota across organization

2. **Usage Analytics**
   - Video encoding minutes used
   - Storage used
   - Bandwidth consumed

3. **Free Trial**
   - 14-day free trial with Pro features
   - Auto-convert to free tier after trial
   - Reminder emails before trial ends

4. **Team/Organization Subscriptions**
   - Shared billing
   - Seat-based pricing
   - Admin management

5. **Annual Billing**
   - Discounted annual plans
   - Prorated upgrades
   - Annual renewal reminders

---

## Support

### Resources

- **Polar Documentation**: https://docs.polar.sh
- **Polar API Reference**: https://docs.polar.sh/api
- **Polar Support**: support@polar.sh
- **Standard Webhooks Spec**: https://github.com/standard-webhooks/standard-webhooks

### Internal Documentation

- Architecture diagram: `/docs/architecture.md` (TODO)
- Database schema: See migrations in `/database/migrations`
- API endpoints: See `/routes/api.php`

---

## Changelog

### 2025-12-13 - Initial Implementation

- Added subscription fields to users table
- Created webhook handling system
- Implemented subscription management API
- Added video recording limits for free tier
- Configured Polar integration
- Created comprehensive documentation

---

**Last Updated:** 2025-12-13
**Version:** 1.0
**Author:** ScreenSense Team
