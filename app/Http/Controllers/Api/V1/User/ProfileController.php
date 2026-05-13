<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/me
     * Get authenticated user profile.
     */
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make(
                request()->user()->load('addresses')
            ),
        ]);
    }

    /**
     * PUT /api/v1/me
     * Update name, email, phone.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data'    => UserResource::make(
                $request->user()->fresh()
            ),
        ]);
    }

    /**
     * PUT /api/v1/me/password
     * Change password after verifying current one.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($request->password),
        ]);

        // Revoke all other tokens for security
        // User will need to log in again on other devices
        $user->tokens()
            ->where('id', '!=', $user->currentAccessToken()->id)
            ->delete();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}