<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new User());
    }

    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user;
    }

    public function updateAvatar(User $user, string $avatarPath): User
    {
        $this->deleteAvatarFile($user);

        $user->avatar = $avatarPath;
        $user->save();

        return $user;
    }

    public function deleteAvatar(User $user): User
    {
        $this->deleteAvatarFile($user);

        $user->avatar = null;
        $user->save();

        return $user;
    }

    protected function deleteAvatarFile(User $user): void
    {
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
