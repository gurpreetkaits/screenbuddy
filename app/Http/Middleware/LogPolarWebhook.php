<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogPolarWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // Log every request to polar/webhook
        if ($request->is('polar/webhook') || $request->is('polar/*')) {
            Log::channel('daily')->info('POLAR WEBHOOK REQUEST INCOMING', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => [
                    'webhook-id' => $request->header('webhook-id'),
                    'webhook-signature' => $request->header('webhook-signature') ? 'PRESENT' : 'MISSING',
                    'webhook-timestamp' => $request->header('webhook-timestamp'),
                    'content-type' => $request->header('content-type'),
                ],
                'body_preview' => substr($request->getContent(), 0, 500),
            ]);
        }

        return $next($request);
    }
}
