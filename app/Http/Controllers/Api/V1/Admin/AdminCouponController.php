<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::when($request->search, fn($q) =>
                $q->where('code', 'ILIKE', "%{$request->search}%")
            )
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $coupons]);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $data         = $request->validated();
        $data['code'] = strtoupper($data['code']);

        $coupon = Coupon::create($data);

        return response()->json([
            'message' => 'Coupon created.',
            'data'    => $coupon,
        ], 201);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);

        return response()->json([
            'message'   => 'Coupon status updated.',
            'is_active' => $coupon->is_active,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        Coupon::findOrFail($id)->delete();

        return response()->json(['message' => 'Coupon deleted.']);
    }
}