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
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Null for anonymous views
            $table->string('ip_address', 45)->nullable(); // Store IP for anonymous tracking
            $table->string('user_agent')->nullable(); // Browser info
            $table->integer('watch_duration')->default(0); // Seconds watched
            $table->boolean('completed')->default(false); // Watched to end?
            $table->timestamp('viewed_at'); // When they watched
            $table->timestamps();

            // Prevent duplicate views (same user/IP viewing same video in short time)
            $table->unique(['video_id', 'user_id', 'viewed_at'], 'unique_user_view');
            $table->index(['video_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
