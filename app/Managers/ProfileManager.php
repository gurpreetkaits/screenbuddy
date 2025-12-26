<?php

namespace App\Managers;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileManager
{
    public function __construct(
        protected UserRepository $users
    ) {}

    public function getProfile(int $userId): array
    {
        $user = $this->users->findOrFail($userId);

        return $this->formatUser($user);
    }

    public function updateProfile(int $userId, array $data, ?UploadedFile $avatar = null): array
    {
        $user = $this->users->findOrFail($userId);

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['username'])) {
            $updateData['username'] = $data['username'];
        }
        if (array_key_exists('bio', $data)) {
            $updateData['bio'] = $data['bio'];
        }
        if (array_key_exists('website', $data)) {
            $updateData['website'] = $data['website'];
        }
        if (array_key_exists('location', $data)) {
            $updateData['location'] = $data['location'];
        }

        if (!empty($updateData)) {
            $user = $this->users->updateProfile($user, $updateData);
        }

        if ($avatar) {
            $avatarPath = $avatar->store('avatars', 'public');
            $user = $this->users->updateAvatar($user, $avatarPath);
        }

        return $this->formatUser($user, true);
    }

    public function deleteAvatar(int $userId): array
    {
        $user = $this->users->findOrFail($userId);
        $user = $this->users->deleteAvatar($user);

        return $this->formatUser($user);
    }

    protected function formatUser(User $user, bool $withAvatarUrl = false): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'bio' => $user->bio,
            'avatar' => $withAvatarUrl && $user->avatar
                ? Storage::url($user->avatar)
                : $user->avatar,
            'website' => $user->website,
            'location' => $user->location,
            'created_at' => $user->created_at,
        ];
    }
}
