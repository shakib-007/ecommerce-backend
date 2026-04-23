<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminBrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::withCount('products')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => BrandResource::collection($brands),
        ]);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $data = [
            'name'    => $request->name,
            'slug'    => Str::slug($request->name),
            'website' => $request->website,
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/brands');
            $data['logo_url'] = Storage::url($path);
        }

        $brand = Brand::create($data);

        return response()->json([
            'message' => 'Brand created.',
            'data'    => new BrandResource($brand),
        ], 201);
    }

    public function update(StoreBrandRequest $request, string $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $data  = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/brands');
            $data['logo_url'] = Storage::url($path);
        }

        $brand->update($data);

        return response()->json([
            'message' => 'Brand updated.',
            'data'    => new BrandResource($brand),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);

        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete brand with products.',
            ], 422);
        }

        $brand->delete();

        return response()->json(['message' => 'Brand deleted.']);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $brand->update(['is_active' => !$brand->is_active]);

        return response()->json([
            'message'   => 'Brand status updated.',
            'is_active' => $brand->is_active,
        ]);
    }
}