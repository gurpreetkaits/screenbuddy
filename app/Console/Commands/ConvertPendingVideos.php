<?php

namespace App\Console\Commands;

use App\Jobs\ConvertVideoToMp4;
use App\Models\Video;
use Illuminate\Console\Command;

class ConvertPendingVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:convert
                            {--all : Convert all videos, not just pending ones}
                            {--failed : Only retry failed conversions}
                            {--id=* : Convert specific video IDs}
                            {--sync : Run conversions synchronously (not queued)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert pending videos to MP4 with faststart for instant seeking';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Video::query()->with('media');

        // Filter by specific IDs if provided
        if ($ids = $this->option('id')) {
            $query->whereIn('id', $ids);
        } elseif ($this->option('all')) {
            // Convert all videos (re-convert everything)
            $this->info('Converting ALL videos...');
        } elseif ($this->option('failed')) {
            // Only retry failed conversions
            $query->where('conversion_status', 'failed');
            $this->info('Retrying failed conversions...');
        } else {
            // Default: only pending videos
            $query->whereIn('conversion_status', ['pending', 'failed']);
            $this->info('Converting pending and failed videos...');
        }

        $videos = $query->get();

        if ($videos->isEmpty()) {
            $this->info('No videos to convert.');
            return Command::SUCCESS;
        }

        $this->info("Found {$videos->count()} video(s) to convert.");

        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $dispatched = 0;
        $skipped = 0;

        foreach ($videos as $video) {
            // Check if video has media
            if (!$video->getFirstMedia('videos')) {
                $this->newLine();
                $this->warn("  Skipping video #{$video->id} - no media file found");
                $skipped++;
                $bar->advance();
                continue;
            }

            // Reset status for retry
            $video->update([
                'conversion_status' => 'pending',
                'conversion_progress' => 0,
                'conversion_error' => null,
            ]);

            if ($this->option('sync')) {
                // Run synchronously (blocking)
                try {
                    $this->newLine();
                    $this->info("  Converting video #{$video->id}: {$video->title}");
                    ConvertVideoToMp4::dispatchSync($video);
                    $this->info("  Completed video #{$video->id}");
                } catch (\Exception $e) {
                    $this->error("  Failed video #{$video->id}: {$e->getMessage()}");
                }
            } else {
                // Dispatch to queue
                ConvertVideoToMp4::dispatch($video);
            }

            $dispatched++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->option('sync')) {
            $this->info("Converted {$dispatched} video(s). Skipped {$skipped}.");
        } else {
            $this->info("Dispatched {$dispatched} video(s) to queue. Skipped {$skipped}.");
            $this->newLine();
            $this->comment('Run the queue worker to process conversions:');
            $this->line('  php artisan queue:work --queue=default --tries=3 --timeout=1800');
        }

        return Command::SUCCESS;
    }
}
