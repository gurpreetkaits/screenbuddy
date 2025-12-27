<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TranscriptSegmentData extends Data
{
    public function __construct(
        public int $id,
        public float $start,
        public float $end,
        public string $text,
    ) {}
}
