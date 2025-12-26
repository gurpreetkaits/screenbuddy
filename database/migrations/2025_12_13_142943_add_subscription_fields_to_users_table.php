<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Polar subscription identifiers
            $table->string('polar_customer_id')->nullable()->unique()->index()->after('location');
            $table->string('polar_subscription_id')->nullable()->unique()->after('polar_customer_id');

            // Subscription status tracking
            $table->enum('subscription_status', ['free', 'active', 'canceled', 'expired', 'incomplete'])
                ->default('free')
                ->index()
                ->after('polar_subscription_id');

            // Subscription period tracking
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_expires_at')->nullable()->index()->after('subscription_started_at');
            $table->timestamp('subscription_canceled_at')->nullable()->after('subscription_expires_at');

            // Product tracking
            $table->string('polar_product_id')->nullable()->after('subscription_canceled_at');
            $table->string('polar_price_id')->nullable()->after('polar_product_id');

            // Video count cache for performance
            $table->unsignedInteger('videos_count')->default(0)->after('polar_price_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'polar_customer_id',
                'polar_subscription_id',
                'subscription_status',
                'subscription_started_at',
                'subscription_expires_at',
                'subscription_canceled_at',
                'polar_product_id',
                'polar_price_id',
                'videos_count',
            ]);
        });
    }
};
