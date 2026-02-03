<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        // Check if user is super admin
        $isSuperAdmin = $user->id === 1 || $user->email === 'technical@redbananas.com';

        return response()->json([
            'user' => $user->load(['defaultTenant', 'tenants']),
            'token' => $token,
            'is_super_admin' => $isSuperAdmin,
            'tenants' => $user->tenants->map(function ($tenant) use ($user) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'role' => $user->getRoleForTenant($tenant),
                ];
            }),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $currentTenant = $request->current_tenant ?? $user->defaultTenant;

        // Check if user is super admin
        $isSuperAdmin = $user->id === 1 || $user->email === 'technical@redbananas.com';

        return response()->json([
            'user' => $user,
            'current_tenant' => $currentTenant,
            'role' => $currentTenant ? $user->getRoleForTenant($currentTenant) : null,
            'is_super_admin' => $isSuperAdmin,
            'tenants' => $user->tenants->map(function ($tenant) use ($user) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'role' => $user->getRoleForTenant($tenant),
                ];
            }),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        // Store new avatar
        $file = $request->file('avatar');
        $filename = 'user_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

        try {
            // Ensure avatars directory exists
            if (!Storage::disk('public')->exists('avatars')) {
                Storage::disk('public')->makeDirectory('avatars');
            }

            // Use Storage::putFileAs for more reliable upload
            $path = Storage::disk('public')->putFileAs('avatars', $file, $filename);

            if (!$path) {
                \Log::error('Avatar upload failed - putFileAs returned false', [
                    'user_id' => $user->id,
                    'filename' => $filename,
                ]);
                return response()->json([
                    'message' => 'Failed to upload avatar',
                ], 500);
            }

            $user->update(['avatar' => $filename]);

            return response()->json([
                'message' => 'Avatar updated successfully',
                'user' => $user->fresh(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Avatar upload exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to upload avatar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
            $user->update(['avatar' => null]);
        }

        return response()->json([
            'message' => 'Avatar removed successfully',
            'user' => $user->fresh(),
        ]);
    }
}
