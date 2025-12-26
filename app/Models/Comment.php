<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'user_id',
        'author_name',
        'content',
        'timestamp_seconds',
    ];

    protected $casts = [
        'timestamp_seconds' => 'integer',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the display name for the comment author.
     */
    public function getAuthorDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->author_name ?? 'Anonymous';
    }
}
