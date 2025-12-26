# Polar Webhook Testing Guide

## Overview

The Polar webhook endpoint at `/api/webhooks/polar` is designed to receive subscription events from Polar.sh. This guide explains how the webhook is configured and how to test it.

## Webhook Configuration

### Public Accessibility

✅ **The webhook is publicly accessible** - No authentication required
✅ **CSRF protection is disabled** for `/api/*` routes
✅ **Signature verification** protects against unauthorized requests

### Route Configuration

**File**: `routes/web.php:13`
```php
Route::post('/api/webhooks/polar', [PolarWebhookController::class, 'handleWebhook'])
    ->name('webhooks.polar');
```

**CSRF Exemption**: `bootstrap/app.php:19-21`
```php
$middleware->validateCsrfTokens(except: [
    'api/*',
]);
```

### Security

The webhook uses **Standard Webhooks specification** for signature verification:
- **webhook-id**: Unique identifier for each webhook
- **webhook-timestamp**: Unix timestamp (prevents replay attacks - 5 minute window)
- **webhook-signature**: HMAC-SHA256 signature (format: `v1,<base64_signature>`)

## Testing

### 1. Automated Tests

Run all webhook tests:
```bash
php artisan test --filter=PolarWebhookControllerTest
```

Run specific public accessibility tests:
```bash
# Test that no authentication is required
php artisan test --filter=test_webhook_endpoint_is_publicly_accessible_without_authentication

# Test that CSRF is exempted
php artisan test --filter=test_webhook_endpoint_is_csrf_exempt
```

### 2. Manual Testing with cURL

#### Basic Webhook Test (Will Fail - No Signature)
```bash
curl -X POST http://localhost:8000/api/webhooks/polar \
  -H "Content-Type: application/json" \
  -d '{"type":"subscription.created","data":{"id":"sub_test_123"}}'

# Expected: 401 Unauthorized (missing signature)
```

#### Test with Valid Signature

**Helper Script**: See `scripts/test-webhook.php` for generating valid signatures

```bash
php scripts/test-webhook.php
```

### 3. Testing with Polar Dashboard

1. Go to [Polar Dashboard](https://polar.sh) → Settings → Webhooks
2. Enter your webhook URL: `https://yourdomain.com/api/webhooks/polar`
3. Add webhook secret to `.env`:
   ```env
   POLAR_WEBHOOK_SECRET=polar_whs_xxxxxxxxxxxxx
   ```
4. Click "Test Webhook" to send a test event
5. Check your application logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### 4. Using ngrok for Local Testing

Expose your local server to Polar:

```bash
# Terminal 1: Start Laravel
php artisan serve

# Terminal 2: Start ngrok
ngrok http 8000

# Use the ngrok URL in Polar webhook settings
# Example: https://abc123.ngrok.io/api/webhooks/polar
```

## Webhook Events Handled

| Event Type | Description | Action |
|------------|-------------|--------|
| `customer.created` | New customer created | Link customer to user |
| `subscription.created` | Subscription initiated | Create subscription record |
| `subscription.active` | Subscription activated | Update user to active status |
| `subscription.updated` | Subscription changed | Update subscription details |
| `subscription.canceled` | Subscription canceled | Mark as canceled (maintains access until period end) |
| `subscription.revoked` | Subscription revoked | Immediately revoke access |
| `checkout.created` | Checkout started | Log event (no action) |
| `order.created` | Order processed | Log event (no action) |

## Expected Responses

### Success
```json
{
  "status": "success"
}
```
**HTTP Status**: 200 OK

### Error Responses

**Missing Signature**
```json
{
  "error": "Invalid signature"
}
```
**HTTP Status**: 401 Unauthorized

**Invalid Event Type**
```json
{
  "error": "Missing event type"
}
```
**HTTP Status**: 400 Bad Request

**Processing Error**
```json
{
  "error": "Processing failed"
}
```
**HTTP Status**: 500 Internal Server Error

## Monitoring

### Check Webhook Logs

```bash
# All webhook activity
tail -f storage/logs/laravel.log | grep "Polar webhook"

# Only errors
tail -f storage/logs/laravel.log | grep "Polar webhook" | grep ERROR
```

### Database Verification

After receiving a webhook, verify the data:

```sql
-- Check subscription updates
SELECT id, email, subscription_status, polar_subscription_id, subscription_expires_at
FROM users
WHERE polar_subscription_id IS NOT NULL;

-- Check subscription history
SELECT * FROM subscription_history
ORDER BY created_at DESC
LIMIT 10;
```

## Troubleshooting

### Webhook Returns 401

**Problem**: Signature verification failed

**Solutions**:
1. Verify webhook secret in `.env` matches Polar dashboard
2. Check signature format: `polar_whs_<base64_key>`
3. Ensure timestamp is within 5 minute window
4. Check webhook headers are correctly sent

### Webhook Returns 404

**Problem**: Route not found

**Solutions**:
1. Clear route cache: `php artisan route:clear`
2. Verify route exists: `php artisan route:list | grep webhooks`
3. Check URL is exactly `/api/webhooks/polar`

### User Not Updated

**Problem**: Webhook processed but user not updated

**Solutions**:
1. Check logs for warnings: `grep "User not found" storage/logs/laravel.log`
2. Verify customer has correct `external_id` (your user ID)
3. Check user exists in database
4. Review subscription history table for event records

## Production Checklist

Before deploying to production:

- [ ] Set correct `POLAR_WEBHOOK_SECRET` in production `.env`
- [ ] Update webhook URL in Polar dashboard to production URL
- [ ] Ensure HTTPS is enabled (Polar requires HTTPS in production)
- [ ] Test webhook with Polar's "Test Webhook" button
- [ ] Monitor logs for first few real webhooks
- [ ] Set up error alerting for webhook failures
- [ ] Document webhook URL for team reference

## Configuration Reference

### Environment Variables

```env
# Required
POLAR_WEBHOOK_SECRET=polar_whs_xxxxxxxxxxxxx

# Optional (for subscription features)
POLAR_API_KEY=polar_sk_xxxxxxxxxxxxx
POLAR_ORGANIZATION_ID=org_xxxxxxxxxxxxx
POLAR_PRODUCT_ID_MONTHLY=prod_xxxxxxxxxxxxx
POLAR_PRODUCT_ID_YEARLY=prod_xxxxxxxxxxxxx
POLAR_ENVIRONMENT=sandbox  # or 'production'
```

### File Locations

- **Controller**: `app/Http/Controllers/PolarWebhookController.php`
- **Routes**: `routes/web.php:13`
- **Tests**: `tests/Feature/PolarWebhookControllerTest.php`
- **Config**: `config/services.php` (Polar settings)
- **CSRF Exemption**: `bootstrap/app.php:19-21`

## Support

For issues with:
- **Webhook signature**: Check [Standard Webhooks spec](https://www.standardwebhooks.com/)
- **Polar integration**: Contact [Polar support](https://polar.sh/support)
- **Application issues**: Check application logs and test suite
