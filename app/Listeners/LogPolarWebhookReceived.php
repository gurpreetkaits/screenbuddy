<?php

namespace App\Listeners;

use Danestves\LaravelPolar\Events\WebhookReceived;
use Illuminate\Support\Facades\Log;

class LogPolarWebhookReceived
{
    public function handle(WebhookReceived $event): void
    {
        Log::channel('daily')->info('Polar webhook received', [
            'type' => $event->payload['type'] ?? 'unknown',
            'payload' => $event->payload,
        ]);
    }
}
