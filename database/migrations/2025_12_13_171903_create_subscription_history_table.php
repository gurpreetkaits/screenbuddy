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
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Event details
            $table->string('event_type'); // created, activated, renewed, canceled, revoked, updated
            $table->string('status'); // active, canceled, expired, etc.

            // Polar identifiers (hidden in API responses)
            $table->string('polar_subscription_id')->nullable();
            $table->string('polar_customer_id')->nullable();
            $table->string('polar_product_id')->nullable();
            $table->string('polar_price_id')->nullable();

            // Subscription period
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            // Amount (in cents)
            $table->integer('amount')->nullable();
            $table->string('currency', 3)->default('USD');

            // Plan info
            $table->string('plan_name')->nullable(); // Monthly, Yearly
            $table->string('plan_interval')->nullable(); // month, year

            // Additional metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_history');
    }
};
