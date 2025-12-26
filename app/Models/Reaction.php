<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'user_id',
        'type',
        'session_id',
    ];

    /**
     * Available reaction types.
     */
    public const TYPES = [
        'like' => '👍',
        'love' => '❤️',
        'fire' => '🔥',
        'clap' => '👏',
        'thinking' => '🤔',
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
     * Get the emoji for this reaction type.
     */
    public function getEmojiAttribute(): string
    {
        return self::TYPES[$this->type] ?? '👍';
    }
}
