<?php

namespace App\Http\Controllers;

use App\Managers\ProfileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileManager $profileManager
    ) {}

    public function show(Request $request)
    {
        $userId = Auth::check() ? Auth::id() : 1;

        return response()->json([
            'user' => $this->profileManager->getProfile($userId),
        ]);
    }

    public function update(Request $request)
    {
        $userId = Auth::check() ? Auth::id() : 1;

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $userId,
            'bio' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'location' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $this->profileManager->updateProfile(
            $userId,
            $validated,
            $request->file('avatar')
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $userId = Auth::check() ? Auth::id() : 1;

        $user = $this->profileManager->deleteAvatar($userId);

        return response()->json([
            'message' => 'Avatar deleted successfully',
            'user' => $user,
        ]);
    }
}
