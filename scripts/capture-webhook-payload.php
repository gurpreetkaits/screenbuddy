#!/usr/bin/env php
<?php

/**
 * Webhook Payload Capture Tool
 *
 * This script monitors your Laravel logs and extracts full webhook payloads
 * from Polar, showing you exactly what data Polar is sending.
 *
 * Usage:
 *   php scripts/capture-webhook-payload.php
 *
 * This will:
 * 1. Monitor storage/logs/laravel.log in real-time
 * 2. Capture and display full webhook payloads when they arrive
 * 3. Show user matching status
 * 4. Help debug why webhooks aren't updating subscriptions
 */
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       Polar Webhook Payload Capture Tool                     ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$logFile = __DIR__.'/../storage/logs/laravel.log';

if (! file_exists($logFile)) {
    echo "❌ Error: Log file not found: {$logFile}\n";
    echo "Please ensure Laravel is running and has written logs.\n";
    exit(1);
}

echo "📡 Monitoring logs for incoming webhooks...\n";
echo "📁 Log file: {$logFile}\n";
echo "⏸️  Press Ctrl+C to stop\n\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

// Open log file and seek to end
$handle = fopen($logFile, 'r');
fseek($handle, 0, SEEK_END);

$buffer = [];
$inWebhook = false;

while (true) {
    $line = fgets($handle);

    if ($line === false) {
        usleep(100000); // 100ms sleep
        clearstatcache();

        continue;
    }

    // Detect webhook start
    if (str_contains($line, '=== POLAR WEBHOOK INCOMING ===')) {
        $inWebhook = true;
        $buffer = [$line];
        echo "\n🔔 NEW WEBHOOK DETECTED\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo date('Y-m-d H:i:s')."\n\n";

        continue;
    }

    if ($inWebhook) {
        $buffer[] = $line;

        // Extract and display webhook data
        if (str_contains($line, 'Polar webhook received')) {
            if (preg_match('/"event":"([^"]+)"/', $line, $matches)) {
                echo '📋 Event Type: '.$matches[1]."\n\n";
            }
        }

        if (str_contains($line, 'Polar webhook raw body')) {
            if (preg_match('/"body_preview":"(.+?)"(?:,|\})/', $line, $matches)) {
                $bodyPreview = stripcslashes($matches[1]);
                $payload = json_decode($bodyPreview, true);

                if ($payload) {
                    echo "📦 Full Payload:\n";
                    echo json_encode($payload, JSON_PRETTY_PRINT)."\n\n";

                    // Extract key information
                    if (isset($payload['data'])) {
                        $data = $payload['data'];

                        echo "🔑 Key Information:\n";
                        echo '  - Subscription ID: '.($data['id'] ?? 'N/A')."\n";
                        echo '  - Customer ID: '.($data['customer_id'] ?? 'N/A')."\n";

                        // Check for external_id
                        $externalId = $data['customer']['external_id'] ??
                                    $data['external_id'] ??
                                    $data['metadata']['user_id'] ??
                                    null;

                        if ($externalId) {
                            echo '  ✅ External ID (User ID): '.$externalId."\n";
                        } else {
                            echo "  ❌ External ID: MISSING!\n";
                            echo "     This is why the webhook can't match to a user.\n";
                        }

                        echo '  - Status: '.($data['status'] ?? 'N/A')."\n";
                        echo '  - Product ID: '.($data['product_id'] ?? 'N/A')."\n";

                        if (isset($data['current_period_end'])) {
                            echo '  - Expires: '.$data['current_period_end']."\n";
                        }
                        echo "\n";
                    }
                }
            }
        }

        // Check for user matching results
        if (str_contains($line, 'Successfully matched user')) {
            if (preg_match('/"user_id":(\d+)/', $line, $matches)) {
                echo '✅ USER MATCHED: User ID '.$matches[1]."\n";
            }
            if (preg_match('/"matched_via":"([^"]+)"/', $line, $matches)) {
                echo '   Method: '.$matches[1]."\n";
            }
            echo "\n";
        }

        if (str_contains($line, 'User not found for subscription')) {
            echo "❌ USER NOT FOUND\n";

            if (preg_match('/"customer_id":"([^"]+)"/', $line, $matches)) {
                echo '   Customer ID tried: '.$matches[1]."\n";
            }
            if (preg_match('/"external_id":(\w+)/', $line, $matches)) {
                $extId = $matches[1];
                echo '   External ID tried: '.($extId === 'null' ? 'NULL (MISSING)' : $extId)."\n";
            }
            echo "\n";
            echo "💡 Why this happens:\n";
            echo "   - External ID not set when creating checkout\n";
            echo "   - OR Polar isn't including it in the webhook\n";
            echo "   - OR Customer record doesn't exist in database\n\n";
        }

        if (str_contains($line, 'Subscription created for user') ||
            str_contains($line, 'Subscription updated for user') ||
            str_contains($line, 'Subscription canceled for user')) {

            echo "✅ SUBSCRIPTION UPDATED IN DATABASE\n";
            if (preg_match('/"user_id":(\d+)/', $line, $matches)) {
                echo '   User ID: '.$matches[1]."\n";
            }
            if (preg_match('/"subscription_id":"([^"]+)"/', $line, $matches)) {
                echo '   Subscription ID: '.$matches[1]."\n";
            }
            echo "\n";
        }

        // End of webhook processing
        if (str_contains($line, '"status":"success"') ||
            str_contains($line, '"error":')) {
            echo "─────────────────────────────────────────────────────────────────\n";
            $inWebhook = false;
            $buffer = [];
        }
    }

    usleep(1000); // 1ms to prevent CPU spinning
}

fclose($handle);
