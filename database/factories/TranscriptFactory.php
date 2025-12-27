<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transcript>
 */
class TranscriptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => Video::factory(),
            'language' => 'en',
            'segments' => [
                [
                    'id' => 0,
                    'start' => 0.0,
                    'end' => 3.5,
                    'text' => 'Hello, this is a sample transcript.',
                ],
                [
                    'id' => 1,
                    'start' => 3.5,
                    'end' => 7.2,
                    'text' => 'This is automatically generated from video.',
                ],
            ],
            'full_text' => 'Hello, this is a sample transcript. This is automatically generated from video.',
            'status' => 'completed',
            'error_message' => null,
            'transcribed_at' => now(),
        ];
    }

    public function pending(): self
    {
        return $this->state([
            'status' => 'pending',
            'segments' => null,
            'full_text' => null,
            'transcribed_at' => null,
        ]);
    }

    public function processing(): self
    {
        return $this->state([
            'status' => 'processing',
            'segments' => null,
            'full_text' => null,
            'transcribed_at' => null,
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status' => 'failed',
            'error_message' => 'Transcription failed due to invalid audio format',
            'segments' => null,
            'full_text' => null,
            'transcribed_at' => null,
        ]);
    }
}
