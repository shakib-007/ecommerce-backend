<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * Get paginated products with all filters applied.
     */
    public function getFilteredProducts(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->active()
            ->with([
                'category',
                'brand',
                'images' => fn($q) => $q->where('is_primary', true),
                'variants' => fn($q) => $q->where('is_active', true),
            ])
            ->withAvg('reviews as rating_avg', 'rating')
            ->withCount('reviews as rating_count');

        // ── Filter by category (includes subcategories) ──────────
        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category'])
                  ->orWhereHas('parent', fn($q2) =>
                      $q2->where('slug', $filters['category'])
                  );
            });
        }

        // ── Filter by brand ──────────────────────────────────────
        if (!empty($filters['brand'])) {
            $query->whereHas('brand', fn($q) =>
                $q->where('slug', $filters['brand'])
            );
        }

        // ── Full-text search ─────────────────────────────────────
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%")
                  ->orWhereHas('brand', fn($q2) =>
                      $q2->where('name', 'ILIKE', "%{$search}%")
                  );
            });
        }

        // ── Price range filter ───────────────────────────────────
        if (!empty($filters['min_price'])) {
            $query->where('base_price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('base_price', '<=', $filters['max_price']);
        }

        // ── Featured only ────────────────────────────────────────
        if (!empty($filters['featured'])) {
            $query->where('is_featured', true);
        }

        // ── In stock only ────────────────────────────────────────
        if (!empty($filters['in_stock'])) {
            $query->whereHas('variants', fn($q) =>
                $q->where('stock_qty', '>', 0)
                  ->where('is_active', true)
            );
        }

        // ── Filter by variant attributes ─────────────────────────
        // e.g. ?attributes[Color]=Black&attributes[Storage]=128GB
        if (!empty($filters['attributes'])) {
            foreach ($filters['attributes'] as $groupName => $value) {
                $query->whereHas('variants.attributeValues', function ($q) use ($groupName, $value) {
                    $q->where('value', $value)
                      ->whereHas('group', fn($q2) =>
                          $q2->where('name', $groupName)
                      );
                });
            }
        }

        // ── Sorting ──────────────────────────────────────────────
        match ($filters['sort'] ?? 'newest') {
            'price_asc'  => $query->orderBy('base_price', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc'),
            'popular'    => $query->orderByDesc('rating_count'),
            default      => $query->orderBy('created_at', 'desc'), // newest
        };

        $perPage = $filters['per_page'] ?? 16;

        return $query->paginate($perPage);
    }

    /**
     * Get a single product with full detail for the product page.
     */
    public function getProductBySlug(string $slug): Product
    {
        return Product::active()
            ->where('slug', $slug)
            ->with([
                'category.parent',
                'brand',
                'images',
                'variants' => fn($q) => $q->where('is_active', true),
                'variants.attributeValues.group',
                'reviews' => fn($q) => $q->where('is_approved', true)
                                         ->latest()
                                         ->with('user'),
            ])
            ->firstOrFail();
    }

    /**
     * Get related products from the same category.
     */
    public function getRelatedProducts(Product $product, int $limit = 8): \Illuminate\Database\Eloquent\Collection
    {
        return Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with([
                'brand',
                'images' => fn($q) => $q->where('is_primary', true),
                'variants' => fn($q) => $q->where('is_active', true),
            ])
            ->withAvg('reviews as rating_avg', 'rating')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}