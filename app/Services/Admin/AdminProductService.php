<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductService
{
    /**
     * Paginated product list for admin table.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Product::withTrashed() // admin can see soft-deleted
            ->with(['category', 'brand', 'variants'])
            ->withCount('variants');

        if (!empty($filters['search'])) {
            $query->where('name', 'ILIKE', "%{$filters['search']}%");
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Show trashed only
        if (!empty($filters['trashed'])) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Create product with variants and images inside a transaction.
     */
    public function create(array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($data, $images) {
            // Generate slug from name
            $slug = $this->generateUniqueSlug($data['name']);

            $product = Product::create([
                'name'        => $data['name'],
                'slug'        => $slug,
                'category_id' => $data['category_id'],
                'brand_id'    => $data['brand_id'] ?? null,
                'description' => $data['description'] ?? null,
                'base_price'  => $data['base_price'],
                'is_featured' => $data['is_featured'] ?? false,
                'is_active'   => $data['is_active'] ?? true,
            ]);

            // Create variants
            foreach ($data['variants'] as $variantData) {
                $variant = ProductVariant::create([
                    'product_id'    => $product->id,
                    'sku'           => $variantData['sku'],
                    'price'         => $variantData['price'],
                    'compare_price' => $variantData['compare_price'] ?? null,
                    'stock_qty'     => $variantData['stock_qty'],
                    'is_active'     => $variantData['is_active'] ?? true,
                ]);

                // Attach attribute values
                if (!empty($variantData['attribute_value_ids'])) {
                    $variant->attributeValues()->attach(
                        $variantData['attribute_value_ids']
                    );
                }
            }

            // Upload and store images
            if (!empty($images)) {
                $this->storeImages($product, $images, $data['primary_image'] ?? 0);
            }

            return $product->load([
                'category',
                'brand',
                'variants.attributeValues.group',
                'images',
            ]);
        });
    }

    /**
     * Update product basic info.
     */
    public function update(Product $product, array $data): Product
    {
        // Regenerate slug only if name changed
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        return $product->fresh([
            'category',
            'brand',
            'variants.attributeValues.group',
            'images',
        ]);
    }

    /**
     * Add a new variant to an existing product.
     */
    public function addVariant(Product $product, array $data): ProductVariant
    {
        $variant = ProductVariant::create([
            'product_id'    => $product->id,
            'sku'           => $data['sku'],
            'price'         => $data['price'],
            'compare_price' => $data['compare_price'] ?? null,
            'stock_qty'     => $data['stock_qty'],
            'is_active'     => $data['is_active'] ?? true,
        ]);

        if (!empty($data['attribute_value_ids'])) {
            $variant->attributeValues()->attach($data['attribute_value_ids']);
        }

        return $variant->load('attributeValues.group');
    }

    /**
     * Update variant stock — used for inventory management.
     */
    public function updateVariantStock(ProductVariant $variant, int $qty): ProductVariant
    {
        $variant->update(['stock_qty' => $qty]);
        return $variant;
    }

    /**
     * Upload images for a product.
     */
    public function storeImages(
        Product $product,
        array   $images,
        int     $primaryIndex = 0
    ): void {
        foreach ($images as $index => $image) {
            $path = $this->uploadImage($image, 'products');

            ProductImage::create([
                'product_id' => $product->id,
                'url'        => Storage::url($path),
                'sort_order' => $index,
                'is_primary' => $index === $primaryIndex,
            ]);
        }
    }

    /**
     * Delete a specific product image.
     */
    public function deleteImage(ProductImage $image): void
    {
        // Delete from storage
        $path = str_replace('/storage/', '', $image->url);
        Storage::delete('public/' . $path);

        $image->delete();
    }

    /**
     * Soft delete a product.
     */
    public function delete(Product $product): void
    {
        $product->delete(); // soft delete — keeps DB record
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(string $productId): Product
    {
        $product = Product::withTrashed()->findOrFail($productId);
        $product->restore();
        return $product;
    }

    /**
     * Upload image to storage and return path.
     */
    private function uploadImage(UploadedFile $file, string $folder): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs("public/{$folder}", $filename);
    }

    /**
     * Generate a unique slug, excluding the current product on update.
     */
    private function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $slug  = Str::slug($name);
        $query = Product::withTrashed()->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            $slug = $slug . '-' . Str::random(5);
        }

        return $slug;
    }
}