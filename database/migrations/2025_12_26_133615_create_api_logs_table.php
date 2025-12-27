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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();

            // Request info
            $table->string('service')->index(); // 'polar', 'stripe', etc.
            $table->string('endpoint', 500); // '/v1/checkouts', '/v1/subscriptions/{id}'
            $table->string('method', 10); // GET, POST, PUT, DELETE
            $table->text('request_body')->nullable(); // Raw request body (JSON/text)

            // Response info
            $table->integer('response_status')->nullable()->index(); // HTTP status code
            $table->longText('response_body')->nullable(); // Raw response (JSON/text/HTML/etc)

            // Performance & status
            $table->integer('duration_ms')->nullable(); // Request duration in milliseconds
            $table->boolean('is_successful')->default(false)->index();
            $table->text('error_message')->nullable();

            // Context
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('correlation_id')->nullable()->index(); // For tracing related requests
            $table->json('context')->nullable(); // Additional context: checkout_id, subscription_id, etc.

            // Indexes
            $table->index(['service', 'created_at']);
            $table->index(['is_successful', 'created_at']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
