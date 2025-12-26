<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    use HasFactory;

    protected $table = 'subscription_history';

    protected $fillable = [
        'user_id',
        'event_type',
        'status',
        'polar_subscription_id',
        'polar_customer_id',
        'polar_product_id',
        'polar_price_id',
        'period_start',
        'period_end',
        'amount',
        'currency',
        'plan_name',
        'plan_interval',
        'metadata',
    ];

    /**
     * Hidden fields - sensitive Polar IDs should not be exposed
     */
    protected $hidden = [
        'polar_subscription_id',
        'polar_customer_id',
        'polar_product_id',
        'polar_price_id',
    ];

    /**
     * Cast attributes
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'amount' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Relationship: History belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable event type
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event_type) {
            'created' => 'Subscription Started',
            'activated' => 'Subscription Activated',
            'renewed' => 'Subscription Renewed',
            'canceled' => 'Subscription Canceled',
            'revoked' => 'Subscription Revoked',
            'updated' => 'Subscription Updated',
            default => ucfirst($this->event_type),
        };
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): ?string
    {
        if (!$this->amount) {
            return null;
        }

        return '$' . number_format($this->amount / 100, 2);
    }

    /**
     * Create a history record for a subscription event
     */
    public static function recordEvent(
        User $user,
        string $eventType,
        string $status,
        array $data = []
    ): self {
        return self::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'status' => $status,
            'polar_subscription_id' => $data['subscription_id'] ?? null,
            'polar_customer_id' => $data['customer_id'] ?? null,
            'polar_product_id' => $data['product_id'] ?? null,
            'polar_price_id' => $data['price_id'] ?? null,
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'plan_name' => $data['plan_name'] ?? null,
            'plan_interval' => $data['plan_interval'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }
}
