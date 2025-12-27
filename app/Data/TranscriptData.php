<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class TranscriptData extends Data
{
    public function __construct(
        public int $id,
        public int $video_id,
        public ?string $language,
        #[DataCollectionOf(TranscriptSegmentData::class)]
        public ?DataCollection $segments,
        public ?string $full_text,
        public string $status,
        public ?string $error_message,
        public ?string $transcribed_at,
        public string $created_at,
        public string $updated_at,
    ) {}
}
