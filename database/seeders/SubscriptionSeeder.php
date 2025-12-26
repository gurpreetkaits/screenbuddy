<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubscriptionHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'gurpreetkait.codes@gmail.com')->first();

        if (!$user) {
            $this->command->error('User with email gurpreetkait.codes@gmail.com not found.');
            return;
        }

        $now = now();
        $yearFromNow = $now->copy()->addYear();

        // Update user subscription fields
        $user->update([
            'polar_customer_id' => 'cus_' . Str::random(24),
            'polar_subscription_id' => 'sub_' . Str::random(24),
            'subscription_status' => 'active',
            'subscription_started_at' => $now,
            'subscription_expires_at' => $yearFromNow,
            'subscription_canceled_at' => null,
            'polar_product_id' => 'prod_' . Str::random(24),
            'polar_price_id' => 'price_' . Str::random(24),
        ]);

        // Create subscription history record
        SubscriptionHistory::create([
            'user_id' => $user->id,
            'event_type' => 'created',
            'status' => 'active',
            'polar_subscription_id' => $user->polar_subscription_id,
            'polar_customer_id' => $user->polar_customer_id,
            'polar_product_id' => $user->polar_product_id,
            'polar_price_id' => $user->polar_price_id,
            'period_start' => $now,
            'period_end' => $yearFromNow,
            'amount' => 4900, // $49.00 yearly in cents
            'currency' => 'USD',
            'plan_name' => 'Yearly',
            'plan_interval' => 'year',
            'metadata' => [
                'seeded' => true,
                'seeded_at' => $now->toISOString(),
            ],
        ]);

        $this->command->info("Yearly subscription created for {$user->email}");
        $this->command->info("Subscription valid from {$now->format('Y-m-d')} to {$yearFromNow->format('Y-m-d')}");
    }
}
