<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\StoreAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->get();

        return response()->json([
            'data' => AddressResource::collection($addresses),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            // If new address is default, unset all others
            if (!empty($data['is_default'])) {
                $user->addresses()->update(['is_default' => false]);
            }

            // First address is always default
            $isFirstAddress = $user->addresses()->count() === 0;

            Address::create([
                ...$data,
                'user_id'    => $user->id,
                'is_default' => $data['is_default'] ?? $isFirstAddress,
            ]);
        });

        return response()->json([
            'message' => 'Address saved.',
            'data'    => AddressResource::collection(
                $user->addresses()->orderByDesc('is_default')->get()
            ),
        ], 201);
    }

    public function update(StoreAddressRequest $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $address = Address::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($user, $address, $request) {
            $data = $request->validated();

            if (!empty($data['is_default'])) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address->update($data);
        });

        return response()->json([
            'message' => 'Address updated.',
            'data'    => new AddressResource($address->fresh()),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $address = Address::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }
}