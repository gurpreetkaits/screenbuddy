# Debugging: Why Subscriptions Go to Wrong User

## Problem
Subscriptions are always being created for user_id 1 instead of the actual user who purchased.

## Root Causes & Solutions

### 1. **Check What Polar is Actually Sending**

The webhook now has detailed logging. When a webhook arrives, check the logs:

```bash
# Watch logs in real-time
tail -f storage/logs/laravel.log | grep "Polar"

# Or filter for subscription events
grep "subscription.created" storage/logs/laravel.log -A 20
```

Look for these log entries:

```
[DEBUG] Polar subscription.created full payload
[DEBUG] Extracted user identification
[INFO] Successfully matched user for subscription
```

**What to check:**
- Does the payload include `data.customer.external_id`?
- Does the payload include `data.metadata.user_id`?
- What is the `customer_id` value?

### 2. **Verify Checkout Creation**

Check that your frontend is actually sending the user's real ID:

```bash
# Check checkout creation logs
grep "Polar checkout created" storage/logs/laravel.log

# Should show something like:
# "user_id": 42,  ← NOT 1!
# "checkout_id": "chk_xyz"
```

**Potential Issue**: If you're testing with a test account, that account might actually be user_id 1.

Check your users table:
```sql
SELECT id, email, name FROM users;
```

Are you testing with the first user (id=1)?

### 3. **Check Polar Dashboard Configuration**

1. Go to [Polar Dashboard](https://polar.sh) → Settings → Webhooks
2. Click "Test Webhook" and send a `subscription.created` event
3. Check if the test payload includes `external_id`

**If external_id is missing from test webhooks:**
- This is expected - Polar test webhooks use dummy data
- Real webhooks from actual purchases WILL include the external_id

### 4. **Verify External ID in Polar Customer**

After a user creates a checkout, verify the customer was created correctly in Polar:

```bash
# Check if customer was created with external_id
curl -X GET "https://api.polar.sh/v1/customers/{customer_id}" \
  -H "Authorization: Bearer ${POLAR_API_KEY}"
```

The response should include:
```json
{
  "id": "cust_xyz",
  "external_id": "42",  ← Your user ID
  "email": "user@example.com"
}
```

## Enhanced Debugging (Now Active)

I've added comprehensive logging to the webhook controller. Here's what happens now:

### Step 1: Payload Logging
```php
Log::debug('Polar subscription.created full payload', [
    'full_payload' => $payload,
    'customer_id' => $customerId,
    'has_customer_object' => isset($data['customer']),
    'has_metadata' => isset($data['metadata']),
]);
```

### Step 2: Extraction Logging
```php
Log::debug('Extracted user identification', [
    'customer_object' => $customer,
    'external_id' => $externalId,
    'metadata' => $data['metadata'] ?? null,
]);
```

### Step 3: Fallback to API Fetch
If `external_id` is missing from webhook payload, the controller now fetches it from Polar's API:

```php
if (!$externalId && $customerId) {
    $externalId = $this->fetchCustomerExternalId($customerId);
}
```

### Step 4: User Matching
```php
Log::debug('User found via external_id', [
    'user_id' => $user->id,
    'external_id' => $externalId,
]);
```

### Step 5: Success Confirmation
```php
Log::info('Successfully matched user for subscription', [
    'user_id' => $user->id,
    'user_email' => $user->email,
    'matched_via' => $externalId ? 'external_id' : 'customer_id',
]);
```

## Testing the Fix

### Test 1: Create a Test User

```sql
-- Create a test user (not user_id 1)
INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Test User 2', 'test2@example.com', '$2y$12$...', NOW(), NOW());

-- Note the new user ID (should be 2 or higher)
SELECT id, email FROM users WHERE email = 'test2@example.com';
```

### Test 2: Create Checkout as That User

Log in as that test user and start a subscription. Check logs:

```bash
tail -f storage/logs/laravel.log | grep "Polar checkout created"
```

Should show:
```
"user_id": 2,  ← NOT 1!
```

### Test 3: Simulate Webhook

Use the test script with the correct user ID:

```bash
# Edit scripts/test-webhook.php and change:
'external_id' => '2',  // Your test user ID

# Then run:
php scripts/test-webhook.php --event=subscription.created
```

Check logs to see if user 2 was matched:
```bash
grep "Successfully matched user" storage/logs/laravel.log
```

## Common Scenarios

### Scenario A: Test Webhooks from Polar Dashboard
**Problem**: Polar's test webhooks don't include real customer data
**Solution**: Only test with real subscriptions or use the test script

### Scenario B: Sandbox vs Production
**Problem**: Different environments might have different customer data
**Solution**: Check `POLAR_ENVIRONMENT` in `.env`:
```env
POLAR_ENVIRONMENT=sandbox  # or production
```

### Scenario C: External ID Not Set
**Problem**: Checkout created without `customer_external_id`
**Solution**: Verify SubscriptionController.php:114 is being executed:
```php
'customer_external_id' => (string) $user->id,
```

Add logging before checkout creation:
```php
Log::info('Creating checkout for user', [
    'user_id' => $user->id,
    'user_email' => $user->email,
    'external_id_will_be' => (string) $user->id,
]);
```

## Quick Diagnostic Commands

```bash
# 1. Check if subscriptions are going to user 1
mysql -u root screensense -e "SELECT id, email, polar_subscription_id FROM users WHERE polar_subscription_id IS NOT NULL;"

# 2. Check subscription history
mysql -u root screensense -e "SELECT user_id, event_type, polar_subscription_id, created_at FROM subscription_history ORDER BY created_at DESC LIMIT 10;"

# 3. Check recent webhook logs
grep "subscription.created" storage/logs/laravel.log | tail -1

# 4. Check if external_id is being sent
grep "customer_external_id" storage/logs/laravel.log | tail -5
```

## Expected Log Flow (Successful)

```
[INFO] Creating checkout for user: {"user_id":42,"user_email":"john@example.com"}
[INFO] Polar checkout created: {"user_id":42,"checkout_id":"chk_xyz"}

... user completes payment ...

[INFO] Polar webhook received: {"event":"subscription.created","subscription_id":"sub_abc"}
[DEBUG] Polar subscription.created full payload: {...}
[DEBUG] Extracted user identification: {"external_id":"42","customer_id":"cust_xyz"}
[INFO] External ID not in webhook payload, fetching customer from Polar API: {"customer_id":"cust_xyz"}
[DEBUG] Fetched customer from Polar API: {"customer_id":"cust_xyz","external_id":"42"}
[DEBUG] User found via external_id: {"user_id":42,"external_id":"42"}
[INFO] Successfully matched user for subscription: {"user_id":42,"matched_via":"external_id"}
[INFO] Subscription created for user: {"user_id":42,"subscription_id":"sub_abc"}
```

## Next Steps

1. **Enable debug logging** in `.env`:
   ```env
   LOG_LEVEL=debug
   ```

2. **Clear logs** to start fresh:
   ```bash
   echo "" > storage/logs/laravel.log
   ```

3. **Create a test subscription** with a non-user-1 account

4. **Review logs** following the expected flow above

5. **Report findings**: Look for where the flow deviates from expected

## If Still Going to User 1

If after all debugging it's still going to user 1, share these logs:
- The full `Polar subscription.created full payload` log entry
- The `Extracted user identification` log entry
- The `Successfully matched user` (or warning if not found) log entry
- The checkout creation log showing which user started the checkout

This will show exactly where the user ID is being lost or changed to 1.
