#!/usr/bin/env php
<?php

/**
 * Check User Subscriptions - Debug Script
 *
 * This script helps diagnose why subscriptions might be going to the wrong user
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\SubscriptionHistory;

echo "═══════════════════════════════════════════════════════════════\n";
echo "User Subscriptions Diagnostic Report\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Check all users
echo "📊 All Users:\n";
echo str_repeat("─", 67) . "\n";

$users = User::all(['id', 'email', 'name', 'polar_customer_id', 'polar_subscription_id', 'subscription_status']);

if ($users->isEmpty()) {
    echo "⚠️  No users found in database\n\n";
} else {
    foreach ($users as $user) {
        echo sprintf(
            "User #%d: %s (%s)\n",
            $user->id,
            $user->email,
            $user->name ?? 'No name'
        );
        echo sprintf(
            "  Polar Customer: %s\n",
            $user->polar_customer_id ?? '(none)'
        );
        echo sprintf(
            "  Subscription: %s (Status: %s)\n",
            $user->polar_subscription_id ?? '(none)',
            $user->subscription_status ?? 'free'
        );
        echo "\n";
    }
}

// 2. Check users with subscriptions
echo "💳 Users with Active Subscriptions:\n";
echo str_repeat("─", 67) . "\n";

$subscribedUsers = User::whereNotNull('polar_subscription_id')->get();

if ($subscribedUsers->isEmpty()) {
    echo "⚠️  No users with subscriptions found\n\n";
} else {
    foreach ($subscribedUsers as $user) {
        echo sprintf(
            "User #%d: %s\n",
            $user->id,
            $user->email
        );
        echo sprintf(
            "  Subscription ID: %s\n",
            $user->polar_subscription_id
        );
        echo sprintf(
            "  Status: %s\n",
            $user->subscription_status
        );
        echo sprintf(
            "  Expires: %s\n",
            $user->subscription_expires_at ? $user->subscription_expires_at->format('Y-m-d H:i:s') : 'N/A'
        );
        echo "\n";
    }
}

// 3. Check subscription history
echo "📜 Recent Subscription History:\n";
echo str_repeat("─", 67) . "\n";

$history = SubscriptionHistory::orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($history->isEmpty()) {
    echo "⚠️  No subscription history found\n\n";
} else {
    foreach ($history as $record) {
        $user = User::find($record->user_id);
        echo sprintf(
            "[%s] User #%d (%s)\n",
            $record->created_at->format('Y-m-d H:i:s'),
            $record->user_id,
            $user ? $user->email : 'User not found'
        );
        echo sprintf(
            "  Event: %s → %s\n",
            $record->event_type,
            $record->status
        );
        echo sprintf(
            "  Subscription: %s\n",
            $record->polar_subscription_id ?? 'N/A'
        );
        echo "\n";
    }
}

// 4. Analysis
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔍 Analysis\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$totalUsers = User::count();
$usersWithSubs = User::whereNotNull('polar_subscription_id')->count();
$activeSubs = User::where('subscription_status', 'active')->count();

echo "Total Users: {$totalUsers}\n";
echo "Users with Subscriptions: {$usersWithSubs}\n";
echo "Active Subscriptions: {$activeSubs}\n\n";

// Check for the specific issue
$user1 = User::find(1);
if ($user1 && $user1->polar_subscription_id) {
    echo "⚠️  WARNING: User #1 has a subscription\n";
    echo "   This might be expected if user #1 is a real account.\n";
    echo "   User #1: {$user1->email}\n\n";

    $user1History = SubscriptionHistory::where('user_id', 1)->count();
    echo "   User #1 has {$user1History} subscription events\n\n";

    if ($usersWithSubs === 1) {
        echo "❌ ISSUE DETECTED: Only user #1 has subscriptions!\n";
        echo "   This suggests webhooks are not matching users correctly.\n\n";
        echo "   Next Steps:\n";
        echo "   1. Check logs: tail -f storage/logs/laravel.log | grep 'Polar'\n";
        echo "   2. Review: docs/debugging-webhook-user-matching.md\n";
        echo "   3. Test checkout with non-user-1 account\n";
    }
}

// Check if multiple users have the same subscription_id
$duplicateSubs = User::select('polar_subscription_id')
    ->whereNotNull('polar_subscription_id')
    ->groupBy('polar_subscription_id')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($duplicateSubs->isNotEmpty()) {
    echo "❌ CRITICAL: Multiple users share the same subscription ID!\n";
    foreach ($duplicateSubs as $dup) {
        $users = User::where('polar_subscription_id', $dup->polar_subscription_id)
            ->get(['id', 'email']);
        echo "   Subscription {$dup->polar_subscription_id} assigned to:\n";
        foreach ($users as $u) {
            echo "     - User #{$u->id}: {$u->email}\n";
        }
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ Diagnostic Complete\n";
echo "═══════════════════════════════════════════════════════════════\n";
