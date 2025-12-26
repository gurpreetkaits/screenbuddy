<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
        'username',
        'bio',
        'avatar',
        'website',
        'location',
        'email_verified_at',
        // Subscription fields
        'polar_customer_id',
        'polar_subscription_id',
        'subscription_status',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_canceled_at',
        'polar_product_id',
        'polar_price_id',
        'videos_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Hide Polar sensitive IDs
        'polar_customer_id',
        'polar_subscription_id',
        'polar_product_id',
        'polar_price_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Subscription timestamp casts
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'subscription_canceled_at' => 'datetime',
            'videos_count' => 'integer',
        ];
    }

    /**
     * Check if user has an active paid subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active';
    }

    /**
     * Check if user can record more videos based on subscription status
     */
    public function canRecordVideo(): bool
    {
        // Paid users have unlimited recording
        if ($this->hasActiveSubscription()) {
            return true;
        }

        // Free users can record only 1 video
        return $this->getVideosCount() < 1;
    }

    /**
     * Get remaining video quota
     * Returns null for unlimited, otherwise remaining count
     */
    public function getRemainingVideoQuota(): ?int
    {
        if ($this->hasActiveSubscription()) {
            return null; // Unlimited
        }

        // Free tier: max 1 video
        $remaining = 1 - $this->getVideosCount();
        return max(0, $remaining);
    }

    /**
     * Check if subscription is in grace period (canceled but not expired)
     */
    public function isSubscriptionInGracePeriod(): bool
    {
        if ($this->subscription_status !== 'canceled') {
            return false;
        }

        if (!$this->subscription_expires_at) {
            return false;
        }

        return $this->subscription_expires_at->isFuture();
    }

    /**
     * Get video count (uses cached value)
     */
    public function getVideosCount(): int
    {
        return $this->videos_count ?? 0;
    }

    /**
     * Recalculate and sync video count from database
     */
    public function syncVideosCount(): int
    {
        $count = $this->videos()->count();
        $this->update(['videos_count' => $count]);
        return $count;
    }

    /**
     * Relationship: User has many videos
     */
    public function videos()
    {
        return $this->hasMany(\App\Models\Video::class);
    }

    /**
     * Relationship: User has many subscription history records
     */
    public function subscriptionHistory()
    {
        return $this->hasMany(SubscriptionHistory::class)->orderBy('created_at', 'desc');
    }
}
