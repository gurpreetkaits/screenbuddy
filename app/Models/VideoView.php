<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoView extends Model
{
    protected $fillable = [
        'video_id',
        'user_id',
        'ip_address',
        'user_agent',
        'watch_duration',
        'completed',
        'viewed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the video that was viewed.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the user who viewed (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
