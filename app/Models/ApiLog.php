<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    protected $fillable = [
        'service',
        'endpoint',
        'method',
        'request_body',
        'response_status',
        'response_body',
        'duration_ms',
        'is_successful',
        'error_message',
        'user_id',
        'correlation_id',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'is_successful' => 'boolean',
    ];

    /**
     * Get the user that made the API request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by service
     */
    public function scopeForService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope to filter successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope to filter failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    /**
     * Scope to filter by correlation ID
     */
    public function scopeByCorrelation($query, string $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_ms) {
            return 'N/A';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms.'ms';
        }

        return round($this->duration_ms / 1000, 2).'s';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->response_status >= 200 && $this->response_status < 300) {
            return 'green';
        }

        if ($this->response_status >= 400 && $this->response_status < 500) {
            return 'yellow';
        }

        if ($this->response_status >= 500) {
            return 'red';
        }

        return 'gray';
    }
}
