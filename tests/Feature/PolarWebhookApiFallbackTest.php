<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PolarWebhookApiFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $webhookSecret = 'test_webhook_secret_key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.polar.webhook_secret' => 'polar_whs_' . base64_encode($this->webhookSecret)]);
        config(['services.polar.api_key' => 'polar_sk_test_12345']);
        config(['services.polar.api_url' => 'https://api.polar.sh']);

        $this->user = User::factory()->create([
            'subscription_status' => 'free',
            'polar_customer_id' => null,
        ]);
    }

    protected function generateWebhookSignature(string $payload, int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $webhookId = 'whk_test_' . uniqid();

        $signedContent = $webhookId . '.' . $timestamp . '.' . $payload;
        $signature = base64_encode(hash_hmac('sha256', $signedContent, $this->webhookSecret, true));

        return [
            'webhook-id' => $webhookId,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => 'v1,' . $signature,
        ];
    }

    public function test_it_fetches_external_id_from_polar_api_when_missing_from_webhook()
    {
        // Mock Polar API response
        Http::fake([
            'https://api.polar.sh/v1/customers/cust_api_fetch_test' => Http::response([
                'id' => 'cust_api_fetch_test',
                'external_id' => (string) $this->user->id,
                'email' => $this->user->email,
                'name' => $this->user->name,
            ], 200),
        ]);

        // Send webhook WITHOUT external_id in payload (simulating missing data)
        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_api_fallback_123',
                'customer_id' => 'cust_api_fetch_test',
                'product_id' => 'prod_test',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
                // No 'customer' object with external_id
                // No metadata with user_id
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        // Verify API was called
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.polar.sh/v1/customers/cust_api_fetch_test'
                && $request->hasHeader('Authorization', 'Bearer polar_sk_test_12345');
        });

        // Verify subscription was assigned to correct user
        $this->user->refresh();
        $this->assertEquals('sub_api_fallback_123', $this->user->polar_subscription_id);
        $this->assertEquals('active', $this->user->subscription_status);
    }

    public function test_it_handles_api_failure_gracefully()
    {
        // Mock Polar API failure
        Http::fake([
            'https://api.polar.sh/v1/customers/cust_api_fail' => Http::response('Not found', 404),
        ]);

        // Send webhook without external_id
        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_api_fail_123',
                'customer_id' => 'cust_api_fail',
                'status' => 'active',
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Webhook should still return success (graceful handling)
        $response->assertOk();

        // But user should NOT be updated (external_id not found)
        $this->user->refresh();
        $this->assertNull($this->user->polar_subscription_id);
    }

    public function test_it_uses_existing_polar_customer_id_before_api_fetch()
    {
        // User already has polar_customer_id stored
        $this->user->update(['polar_customer_id' => 'cust_existing_123']);

        // Don't need to mock API - should not be called

        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_existing_cust_123',
                'customer_id' => 'cust_existing_123',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
                // No external_id provided
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        // User should be matched via polar_customer_id (no API call needed)
        $this->user->refresh();
        $this->assertEquals('sub_existing_cust_123', $this->user->polar_subscription_id);

        // Verify NO API calls were made
        Http::assertNothingSent();
    }

    public function test_external_id_in_webhook_takes_precedence_over_api_fetch()
    {
        // Webhook has external_id, so API should NOT be called

        $payload = json_encode([
            'type' => 'subscription.created',
            'data' => [
                'id' => 'sub_no_api_needed',
                'customer_id' => 'cust_with_external',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->toIso8601String(),
                'customer' => [
                    'external_id' => (string) $this->user->id, // External ID provided
                ],
            ],
        ]);

        $headers = $this->generateWebhookSignature($payload);

        $response = $this->call('POST', '/api/webhooks/polar', [], [], [], [
            'HTTP_webhook-id' => $headers['webhook-id'],
            'HTTP_webhook-timestamp' => $headers['webhook-timestamp'],
            'HTTP_webhook-signature' => $headers['webhook-signature'],
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        // User matched via external_id
        $this->user->refresh();
        $this->assertEquals('sub_no_api_needed', $this->user->polar_subscription_id);

        // NO API call should have been made
        Http::assertNothingSent();
    }
}
