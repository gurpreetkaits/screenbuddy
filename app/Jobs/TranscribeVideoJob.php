<?php

namespace App\Jobs;

use App\Managers\TranscriptManager;
use App\Models\Transcript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class TranscribeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public $tries = 3;

    public $backoff = 60;

    public function __construct(
        public Transcript $transcript
    ) {}

    public function handle(TranscriptManager $transcriptManager): void
    {
        try {
            $transcriptManager->markAsProcessing($this->transcript);

            $video = $this->transcript->video;

            $videoMedia = $video->getFirstMedia('videos');
            if (! $videoMedia) {
                throw new \Exception('Video file not found');
            }

            $videoPath = $videoMedia->getPath();

            if (! file_exists($videoPath)) {
                throw new \Exception('Video file does not exist at path: '.$videoPath);
            }

            Log::info('Starting transcription for video ID: '.$video->id, [
                'transcript_id' => $this->transcript->id,
                'video_path' => $videoPath,
                'file_size' => filesize($videoPath),
            ]);

            $response = OpenAI::audio()->transcribe([
                'model' => 'whisper-1',
                'file' => fopen($videoPath, 'r'),
                'response_format' => 'verbose_json',
                'timestamp_granularities' => ['segment'],
            ]);

            $language = $response->language ?? null;
            $fullText = $response->text ?? '';

            $segments = collect($response->segments ?? [])->map(function ($segment) {
                return [
                    'start' => $segment->start,
                    'end' => $segment->end,
                    'text' => $segment->text,
                ];
            })->toArray();

            $transcriptManager->updateTranscriptData(
                $this->transcript,
                $language,
                $segments,
                $fullText
            );

            $transcriptManager->markAsCompleted($this->transcript);

            Log::info('Transcription completed successfully', [
                'transcript_id' => $this->transcript->id,
                'video_id' => $video->id,
                'language' => $language,
                'segments_count' => count($segments),
            ]);
        } catch (\Exception $e) {
            Log::error('Transcription failed', [
                'transcript_id' => $this->transcript->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $transcriptManager->markAsFailed($this->transcript, $e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $transcriptManager = app(TranscriptManager::class);
        $transcriptManager->markAsFailed(
            $this->transcript,
            'Job failed after '.$this->tries.' attempts: '.$exception->getMessage()
        );
    }
}
