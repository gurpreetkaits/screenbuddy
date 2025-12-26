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
        Schema::table('videos', function (Blueprint $table) {
            // Conversion status: pending, processing, completed, failed
            $table->string('conversion_status')->default('pending')->after('duration');
            // Store original file extension (webm, mov, etc.)
            $table->string('original_extension')->nullable()->after('conversion_status');
            // Conversion progress percentage (0-100)
            $table->unsignedTinyInteger('conversion_progress')->default(0)->after('original_extension');
            // Error message if conversion failed
            $table->text('conversion_error')->nullable()->after('conversion_progress');
            // Timestamp when conversion completed
            $table->timestamp('converted_at')->nullable()->after('conversion_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'conversion_status',
                'original_extension',
                'conversion_progress',
                'conversion_error',
                'converted_at',
            ]);
        });
    }
};
