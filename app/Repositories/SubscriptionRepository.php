<?php

namespace App\Repositories;

use App\Models\SubscriptionHistory;
use App\Models\User;
use Illuminate\Support\Collection;

class SubscriptionRepository
{
    public function getUserSubscriptionHistory(User $user, int $limit = 50): Collection
    {
        return $user->subscriptionHistory()
            ->select([
                'id',
                'event_type',
                'status',
                'period_start',
                'period_end',
                'amount',
                'currency',
                'plan_name',
                'plan_interval',
                'created_at',
            ])
            ->limit($limit)
            ->get();
    }

    public function updateUserSubscription(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function recordSubscriptionEvent(User $user, string $eventType, string $status, array $data): SubscriptionHistory
    {
        return SubscriptionHistory::recordEvent($user, $eventType, $status, $data);
    }

    public function clearCustomerId(User $user): bool
    {
        return $user->update(['polar_customer_id' => null]);
    }
}
