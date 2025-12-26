<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class RegenerateVideoThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:regenerate-thumbnails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate thumbnails for all existing videos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting thumbnail regeneration...');

        $videos = Video::with('media')->get();

        if ($videos->isEmpty()) {
            $this->warn('No videos found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$videos->count()} videos.");
        $bar = $this->output->createProgressBar($videos->count());

        $processed = 0;
        $failed = 0;

        foreach ($videos as $video) {
            try {
                $media = $video->getFirstMedia('videos');

                if ($media) {
                    // Trigger thumbnail generation by performing media conversions
                    $conversionJob = new \Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob(
                        ['thumb'],
                        $media
                    );
                    $conversionJob->handle();
                    $processed++;
                } else {
                    $this->warn("\nVideo ID {$video->id} has no media file.");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("\nFailed to regenerate thumbnail for video ID {$video->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info("Thumbnail regeneration complete!");
        $this->info("Processed: {$processed}");

        if ($failed > 0) {
            $this->warn("Failed: {$failed}");
        }

        return Command::SUCCESS;
    }
}
