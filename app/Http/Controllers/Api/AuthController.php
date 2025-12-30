<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log the login
        AuditLog::log('login', $user, null, null, 'User logged in');

        // Load relationships
        $user->load(['branch', 'store', 'branches', 'stores']);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'store_id' => $user->store_id,
                'branch' => $user->branch,
                'store' => $user->store,
                'branches' => $user->branches,
                'stores' => $user->stores,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        // Log the logout
        AuditLog::log('logout', $request->user(), null, null, 'User logged out');

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['branch', 'store', 'branches', 'stores']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'store_id' => $user->store_id,
                'branch' => $user->branch,
                'store' => $user->store,
                'branches' => $user->branches,
                'stores' => $user->stores,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password',
            'password' => 'sometimes|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }
            $validated['password'] = Hash::make($validated['password']);
        }

        $oldValues = $user->only(['name', 'email']);
        $user->update($validated);

        AuditLog::log('profile_updated', $user, $oldValues, $user->only(['name', 'email']), 'User updated profile');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
}

