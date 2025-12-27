<?php

namespace App\Services;

use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApiLogger
{
    protected ?User $user = null;

    protected ?string $correlationId = null;

    protected array $context = [];

    protected string $service = 'unknown';

    protected bool $enabled = true;

    /**
     * Set the user making the API request
     */
    public function forUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the service name (e.g., 'polar', 'stripe')
     */
    public function forService(string $service): self
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Set correlation ID for tracing related requests
     */
    public function withCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Add additional context to the log
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Disable logging for this request
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Create a new HTTP client with logging enabled
     */
    public function http(): PendingRequest
    {
        $correlationId = $this->correlationId ?? Str::uuid()->toString();

        return Http::beforeSending(function ($request, $options) use ($correlationId) {
            // Store request start time
            $options['__api_logger'] = [
                'start_time' => microtime(true),
                'correlation_id' => $correlationId,
                'service' => $this->service,
                'user' => $this->user,
                'context' => $this->context,
                'enabled' => $this->enabled,
            ];
        })->withOptions(['__api_logger' => [
            'start_time' => microtime(true),
            'correlation_id' => $correlationId,
            'service' => $this->service,
            'user' => $this->user,
            'context' => $this->context,
            'enabled' => $this->enabled,
        ]])->afterSending(function ($request, $options, $response) {
            $this->logRequest($request, $options, $response);
        });
    }

    /**
     * Log an API request manually
     */
    public function log(
        string $method,
        string $url,
        array $headers = [],
        $body = null,
        ?Response $response = null,
        ?\Throwable $exception = null
    ): ?ApiLog {
        if (! $this->enabled) {
            return null;
        }

        $startTime = microtime(true);
        $parsedUrl = parse_url($url);
        $endpoint = ($parsedUrl['path'] ?? '').(isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '');

        $logData = [
            'service' => $this->service,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'request_body' => is_array($body) ? json_encode($body) : (string) $body,
            'user_id' => $this->user?->id,
            'correlation_id' => $this->correlationId ?? Str::uuid()->toString(),
            'context' => $this->context,
        ];

        if ($response) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $logData['response_status'] = $response->status();
            $logData['response_body'] = $response->body(); // Store raw response
            $logData['duration_ms'] = $duration;
            $logData['is_successful'] = $response->successful();

            if (! $response->successful()) {
                $logData['error_message'] = "HTTP {$response->status()}";
            }
        }

        if ($exception) {
            $logData['is_successful'] = false;
            $logData['error_message'] = $exception->getMessage();
        }

        return ApiLog::create($logData);
    }

    /**
     * Log request from Http client callback
     */
    protected function logRequest($request, $options, $response): void
    {
        $loggerOptions = $options['__api_logger'] ?? null;

        if (! $loggerOptions || ! ($loggerOptions['enabled'] ?? true)) {
            return;
        }

        $startTime = $loggerOptions['start_time'] ?? microtime(true);
        $duration = (int) ((microtime(true) - $startTime) * 1000);

        $url = (string) $request->url();
        $parsedUrl = parse_url($url);
        $endpoint = ($parsedUrl['path'] ?? '').(isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '');

        // Get request body as raw string
        $requestBody = $request->body() ?? null;

        $logData = [
            'service' => $loggerOptions['service'] ?? 'unknown',
            'endpoint' => $endpoint,
            'method' => strtoupper($request->method()),
            'request_body' => $requestBody,
            'response_status' => $response->status(),
            'response_body' => $response->body(), // Store raw response
            'duration_ms' => $duration,
            'is_successful' => $response->successful(),
            'user_id' => $loggerOptions['user']?->id,
            'correlation_id' => $loggerOptions['correlation_id'] ?? Str::uuid()->toString(),
            'context' => $loggerOptions['context'] ?? [],
        ];

        if (! $response->successful()) {
            $logData['error_message'] = "HTTP {$response->status()}: ".substr($response->body(), 0, 500);
        }

        ApiLog::create($logData);
    }

    /**
     * Create a new instance
     */
    public static function make(): self
    {
        return new self;
    }
}
