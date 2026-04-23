<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::where('role', 'customer')
            ->when($request->search, fn($q) =>
                $q->where('name', 'ILIKE', "%{$request->search}%")
                  ->orWhere('email', 'ILIKE', "%{$request->search}%")
            )
            ->when(isset($request->is_active), fn($q) =>
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->withCount('orders')
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with(['addresses', 'orders' => fn($q) =>
            $q->latest()->limit(5)->with('latestPayment')
        ])->findOrFail($id);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent admin from deactivating themselves
        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Cannot deactivate an admin account.',
            ], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message'   => $user->is_active
                ? 'User activated.'
                : 'User deactivated.',
            'is_active' => $user->is_active,
        ]);
    }
}