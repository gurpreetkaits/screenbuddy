<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class RegenerateThumbnail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:thumbnail {id : The ID of the video} {--all : Regenerate thumbnails for all videos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate thumbnail for a video by ID or all videos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Show current FFmpeg config (use config() not env() for cache compatibility)
        $this->info('FFmpeg Config:');
        $this->line('  FFMPEG_PATH: ' . config('media-library.ffmpeg_path', 'not set'));
        $this->line('  FFPROBE_PATH: ' . config('media-library.ffprobe_path', 'not set'));
        $this->newLine();

        if ($this->option('all')) {
            return $this->regenerateAll();
        }

        $videoId = $this->argument('id');

        $video = Video::find($videoId);

        if (!$video) {
            $this->error("Video with ID {$videoId} not found.");
            return 1;
        }

        $this->info("Regenerating thumbnail for video: {$video->title} (ID: {$video->id})");

        // Check if video file exists
        $videoMedia = $video->getFirstMedia('videos');
        if (!$videoMedia) {
            $this->error("No video file found for video ID {$videoId}");
            return 1;
        }

        $this->line("Video file: " . $videoMedia->getPath());
        $this->line("File exists: " . (file_exists($videoMedia->getPath()) ? 'YES' : 'NO'));
        $this->line("Duration: " . $video->duration . " seconds");
        $this->newLine();

        try {
            $this->info("Generating thumbnail...");
            $video->generateThumbnailFromMidpoint();

            // Check if thumbnail was created
            $thumbnail = $video->fresh()->getFirstMedia('thumbnails');
            if ($thumbnail) {
                $this->info("Thumbnail generated successfully!");
                $this->line("Thumbnail path: " . $thumbnail->getPath());
                $this->line("Thumbnail URL: " . $thumbnail->getUrl());
            } else {
                $this->warn("Thumbnail generation completed but no thumbnail found.");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate thumbnail: " . $e->getMessage());
            $this->newLine();
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Regenerate thumbnails for all videos.
     */
    protected function regenerateAll()
    {
        $videos = Video::all();
        $this->info("Regenerating thumbnails for {$videos->count()} videos...");
        $this->newLine();

        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($videos as $video) {
            try {
                $video->generateThumbnailFromMidpoint();
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed for video {$video->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Completed: {$success} success, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }
}
