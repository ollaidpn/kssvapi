<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\LocalCategory;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Shortcut;

class LocalController extends Controller
{
    /**
     * Get paginated products with filters
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            $query = Item::with(['category:id,name', 'brand:id,name'])
                ->where('status', 'active');
            
            // Global filter: show only products with valid images
            $appInfo = \App\Models\AppInfo::first();
            if ($appInfo && $appInfo->show_only_with_images) {
                $query->where(function($q) {
                    $q->whereNotNull('image')
                      ->where('image', '!=', '')
                      ->where('image', 'NOT LIKE', '%no-image%')
                      ->where('image', 'NOT LIKE', '%aucune%')
                      ->where('image', 'NOT LIKE', '%aucunimage%');
                });
            }
            
            // Filter by category
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            // Filter by brand
            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }
            
            // Price range filter
            if ($request->filled('min_price')) {
                $query->where('price', '>=', (float) $request->min_price);
            }
            if ($request->filled('max_price')) {
                $query->where('price', '<=', (float) $request->max_price);
            }
            
            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('code', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }
            
            // Pagination
            $perPage = $request->get('per_page', 20);
            $products = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            // Transform products with full image URLs
            $transformedProducts = $products->getCollection()->map(function ($item) {
                return $this->transformProduct($item);
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedProducts,
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getProducts error', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des produits'
            ], 500);
        }
    }
    
    /**
     * Get single product detail
     */
    public function getProduct(int $id): JsonResponse
    {
        try {
            $product = Item::with(['category:id,name', 'brand:id,name'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $this->transformProduct($product)
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getProduct error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Produit non trouvé'
            ], 404);
        }
    }
    
    /**
     * Search products by query
     */
    public function searchProducts(string $query): JsonResponse
    {
        try {
            $productQuery = Item::with(['category:id,name'])
                ->where('status', 'active')
                ->where(function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('code', 'LIKE', "%{$query}%");
                });
            
            // Global filter: show only products with valid images
            $appInfo = \App\Models\AppInfo::first();
            if ($appInfo && $appInfo->show_only_with_images) {
                $productQuery->where(function($q) {
                    $q->whereNotNull('image')
                      ->where('image', '!=', '')
                      ->where('image', 'NOT LIKE', '%no-image%')
                      ->where('image', 'NOT LIKE', '%aucune%')
                      ->where('image', 'NOT LIKE', '%aucunimage%');
                });
            }
            
            $products = $productQuery->orderBy('name')
                ->limit(50)
                ->get();
            
            $transformedProducts = $products->map(function ($item) {
                return $this->transformProduct($item);
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedProducts
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::searchProducts error', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }
    
    /**
     * Get all categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = LocalCategory::withCount('items')
                ->orderBy('name')
                ->get();
            
            $transformedCategories = $categories->map(function ($category) {
                return $this->transformCategory($category);
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedCategories
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getCategories error', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des catégories'
            ], 500);
        }
    }
    
    /**
     * Get single category with products
     */
    public function getCategory(int $id): JsonResponse
    {
        try {
            $category = LocalCategory::withCount('items')
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $this->transformCategory($category)
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getCategory error', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée'
            ], 404);
        }
    }
    
    /**
     * Get related products (same category, excluding current product)
     */
    public function getRelatedProducts(int $productId, Request $request): JsonResponse
    {
        try {
            $product = Item::findOrFail($productId);
            $limit = $request->get('limit', 10);
            
            $relatedQuery = Item::with(['category:id,name'])
                ->where('status', 'active')
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $productId);
            
            // Global filter: show only products with valid images
            $appInfo = \App\Models\AppInfo::first();
            if ($appInfo && $appInfo->show_only_with_images) {
                $relatedQuery->where(function($q) {
                    $q->whereNotNull('image')
                      ->where('image', '!=', '')
                      ->where('image', 'NOT LIKE', '%no-image%')
                      ->where('image', 'NOT LIKE', '%aucune%')
                      ->where('image', 'NOT LIKE', '%aucunimage%');
                });
            }
            
            $relatedProducts = $relatedQuery->limit($limit)->get();
            
            $transformedProducts = $relatedProducts->map(function ($item) {
                return $this->transformProduct($item);
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedProducts
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getRelatedProducts error', [
                'productId' => $productId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }
    
    /**
     * Transform product model to API response format
     */
    private function transformProduct(Item $item): array
    {
        $baseUrl = config('app.url');
        
        // Build image URL
        $image = $item->image;
        if ($image && !str_starts_with($image, 'http')) {
            $image = Shortcut::fileExistsOnServer($image) 
                ? $baseUrl . '/' . $image 
                : ($item->original_image ?? $baseUrl . '/no-image.png');
        }
        
        // Build gallery URLs
        $images = [];
        if (!empty($item->images) && is_array($item->images)) {
            foreach ($item->images as $img) {
                if ($img && !str_starts_with($img, 'http')) {
                    $images[] = Shortcut::fileExistsOnServer($img) 
                        ? $baseUrl . '/' . $img 
                        : $img;
                } else {
                    $images[] = $img;
                }
            }
        }
        
        return [
            'id' => $item->id,
            'sync_id' => $item->sync_id,
            'code' => $item->code ?? 'N/A',
            'name' => $item->name,
            'description' => $item->description,
            'price' => (float) $item->price,
            'sale_price' => $item->sale_price ? (float) $item->sale_price : null,
            'stock' => (int) ($item->stock ?? 0),
            'image' => $image ?? $baseUrl . '/no-image.png',
            'gallery' => $images,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name,
            'brand_id' => $item->brand_id,
            'brand_name' => $item->brand?->name,
            'status' => $item->status,
        ];
    }
    
    /**
     * Transform category model to API response format
     */
    private function transformCategory(LocalCategory $category): array
    {
        $baseUrl = config('app.url');
        
        $logo = $category->logo;
        if ($logo && !str_starts_with($logo, 'http')) {
            $logo = Shortcut::fileExistsOnServer($logo) 
                ? $baseUrl . '/' . $logo 
                : ($category->original_logo ?? $baseUrl . '/no-image.png');
        }
        
        return [
            'id' => $category->id,
            'sync_id' => $category->sync_id,
            'name' => $category->name,
            'logo' => $logo ?? $baseUrl . '/no-image.png',
            'parent_id' => $category->parent_id,
            'items_count' => $category->items_count ?? 0,
        ];
    }
}
