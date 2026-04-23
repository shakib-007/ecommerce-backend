<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withTrashed()
            ->with('parent', 'children')
            ->withCount('products')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data         = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/categories');
            $data['image_url'] = Storage::url($path);
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created.',
            'data'    => new CategoryResource($category),
        ], 201);
    }

    public function update(StoreCategoryRequest $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $data     = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/categories');
            $data['image_url'] = Storage::url($path);
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated.',
            'data'    => new CategoryResource($category->fresh('parent', 'children')),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with products. Move products first.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}