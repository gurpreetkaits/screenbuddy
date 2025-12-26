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
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // e.g., 'like', 'love', 'fire', 'clap', 'thinking'
            $table->string('session_id')->nullable(); // For anonymous reactions
            $table->timestamps();

            $table->index('video_id');
            $table->unique(['video_id', 'user_id', 'type'], 'unique_user_reaction');
            $table->unique(['video_id', 'session_id', 'type'], 'unique_session_reaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
