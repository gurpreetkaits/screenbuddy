<?php

namespace App\Jobs;

use App\Managers\TranscriptManager;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConvertVideoToMp4 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     * Set to 30 minutes for long videos.
     */
    public int $timeout = 1800;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Video $video
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $video = $this->video;

        Log::info('Starting video conversion', [
            'video_id' => $video->id,
            'title' => $video->title,
        ]);

        // Get the current media
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            Log::error('No media found for video', ['video_id' => $video->id]);
            $this->markAsFailed($video, 'No media file found');

            return;
        }

        $inputPath = $media->getPath();
        $mimeType = $media->mime_type;
        $originalExtension = pathinfo($inputPath, PATHINFO_EXTENSION);

        // Store original extension
        $video->update(['original_extension' => $originalExtension]);

        // Skip if already MP4 with faststart (check file structure)
        if ($mimeType === 'video/mp4' && $this->hasFastStart($inputPath)) {
            Log::info('Video already MP4 with faststart, skipping conversion', [
                'video_id' => $video->id,
            ]);
            $video->update([
                'conversion_status' => 'completed',
                'conversion_progress' => 100,
                'converted_at' => now(),
            ]);

            return;
        }

        // Mark as processing
        $video->update([
            'conversion_status' => 'processing',
            'conversion_progress' => 10,
        ]);

        // Prepare output path
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir.'/converted_'.$video->id.'_'.time().'.mp4';

        try {
            // Build FFmpeg command with faststart for instant seeking
            $ffmpegPath = config('media-library.ffmpeg_path', '/opt/homebrew/bin/ffmpeg');

            // Memory-optimized FFmpeg settings for low-memory servers:
            // -threads 1: Single thread to minimize memory usage
            // -preset ultrafast: Fastest preset, uses least memory
            // -tune fastdecode: Optimizes for playback, reduces encoding complexity
            // -max_muxing_queue_size 1024: Limits muxing buffer (prevents memory bloat)
            // -bufsize 1M: Limits rate control buffer
            // -movflags +faststart: Puts moov atom at beginning for instant seeking
            $command = sprintf(
                '%s -y -threads 1 -i %s -c:v libx264 -preset ultrafast -tune fastdecode -crf 23 -maxrate 2M -bufsize 1M -c:a aac -b:a 128k -max_muxing_queue_size 1024 -movflags +faststart %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            Log::info('Running FFmpeg conversion', [
                'video_id' => $video->id,
                'command' => $command,
            ]);

            // Execute conversion with progress tracking
            $video->update(['conversion_progress' => 20]);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);

            Log::info('FFmpeg output', [
                'video_id' => $video->id,
                'return_code' => $returnCode,
                'output_length' => strlen($outputText),
            ]);

            if ($returnCode !== 0) {
                throw new \Exception("FFmpeg failed with code $returnCode: ".substr($outputText, -500));
            }

            if (! file_exists($outputPath)) {
                throw new \Exception('Output file was not created');
            }

            $outputSize = filesize($outputPath);
            if ($outputSize < 1000) {
                throw new \Exception("Output file is too small: $outputSize bytes");
            }

            $video->update(['conversion_progress' => 80]);

            // Replace the original media with the converted file
            $video->clearMediaCollection('videos');

            $video->addMedia($outputPath)
                ->usingFileName('video_'.$video->id.'.mp4')
                ->toMediaCollection('videos');

            $video->update(['conversion_progress' => 95]);

            // Regenerate thumbnail from the converted video
            $video->generateThumbnailFromMidpoint();

            // Mark as completed
            $video->update([
                'conversion_status' => 'completed',
                'conversion_progress' => 100,
                'conversion_error' => null,
                'converted_at' => now(),
            ]);

            Log::info('Video conversion completed successfully', [
                'video_id' => $video->id,
                'original_extension' => $originalExtension,
                'output_size' => $outputSize,
            ]);

            // Clean up temp file if it still exists
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            // Automatically start transcription after successful conversion
            $transcriptManager = app(TranscriptManager::class);
            $transcriptManager->createTranscript($video);

            Log::info('Transcription job dispatched', ['video_id' => $video->id]);

        } catch (\Exception $e) {
            Log::error('Video conversion failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            // Clean up temp file
            if (isset($outputPath) && file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $this->markAsFailed($video, $e->getMessage());

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Check if MP4 file already has faststart (moov atom at beginning).
     */
    private function hasFastStart(string $filePath): bool
    {
        // Read first 32 bytes to check for ftyp and moov atoms
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return false;
        }

        $header = fread($handle, 32);
        fclose($handle);

        // Check if moov atom appears early in the file (indicates faststart)
        // This is a simplified check - moov should come before mdat for faststart
        return str_contains($header, 'ftyp') && str_contains($header, 'moov');
    }

    /**
     * Mark the video conversion as failed.
     */
    private function markAsFailed(Video $video, string $error): void
    {
        $video->update([
            'conversion_status' => 'failed',
            'conversion_error' => substr($error, 0, 1000), // Limit error message length
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Video conversion job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->markAsFailed($this->video, $exception?->getMessage() ?? 'Unknown error');
    }
}
