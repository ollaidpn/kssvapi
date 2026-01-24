<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Shortcut;

class LocalController extends Controller
{
    /**
     * Apply global image filter if enabled in app settings
     */
    private function applyImageFilter($query): void
    {
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
    }

    /**
     * Get paginated products with filters
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            $query = Item::with(['category:id,name', 'brand:id,name'])
                ->where('status', 'active');
            
            // Global filter
            $this->applyImageFilter($query);
            
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
            
            // Global filter
            $this->applyImageFilter($productQuery);
            
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
     * Get recently added products
     * Used for "Articles Récemment Ajoutés" section
     */
    public function getRecentProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 30);
            
            $query = Item::with(['category:id,name'])
                ->where('status', 'active');
            
            $this->applyImageFilter($query);
            
            $products = $query->orderByDesc('created_at')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $products->map(fn($p) => $this->transformProduct($p))
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getRecentProducts error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }

    /**
     * Get random products (shuffled from database)
     * Used for "Articles qui pourraient vous intéresser" section
     */
    public function getRandomProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            
            $query = Item::with(['category:id,name'])
                ->where('status', 'active');
            
            $this->applyImageFilter($query);
            
            // MySQL: ORDER BY RAND()
            $products = $query->inRandomOrder()
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $products->map(fn($p) => $this->transformProduct($p))
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getRandomProducts error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }

    /**
     * Get products by category ID
     * Used for "Produits Populaires" section
     */
    public function getProductsByCategory(int $categoryId, Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 16);
            
            $query = Item::with(['category:id,name'])
                ->where('status', 'active')
                ->where('category_id', $categoryId);
            
            $this->applyImageFilter($query);
            
            // Always return in random order for variety
            $products = $query->inRandomOrder()
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $products->map(fn($p) => $this->transformProduct($p))
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getProductsByCategory error', [
                'categoryId' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }
    
    /**
     * Get all categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = Category::withCount('items')
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
     * Get top categories with most products (random selection from top N)
     * Used for HeroSection sidebar
     */
    public function getTopCategories(Request $request): JsonResponse
    {
        try {
            $topCount = $request->get('top', 40);
            $returnCount = $request->get('limit', 10);
            
            $categories = Category::withCount('items')
                ->having('items_count', '>', 0)
                ->orderByDesc('items_count')
                ->limit($topCount)
                ->get();
            
            // Shuffle and take the requested number
            $shuffled = $categories->shuffle()->take($returnCount);
            
            return response()->json([
                'success' => true,
                'data' => $shuffled->map(fn($c) => $this->transformCategory($c))
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getTopCategories error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }
    
    /**
     * Get single category with products
     */
    public function getCategory(int $id): JsonResponse
    {
        try {
            $category = Category::withCount('items')
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
            
            $this->applyImageFilter($relatedQuery);
            
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
     * Get app info (public endpoint for contact page)
     */
    public function getAppInfo(): JsonResponse
    {
        try {
            $appInfo = \App\Models\AppInfo::first();
            
            if (!$appInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Informations non disponibles'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'name' => $appInfo->name,
                    'ccphone1' => $appInfo->ccphone1,
                    'phone1' => $appInfo->phone1,
                    'ccphone2' => $appInfo->ccphone2,
                    'phone2' => $appInfo->phone2,
                    'email1' => $appInfo->email1,
                    'email2' => $appInfo->email2,
                    'latitude' => $appInfo->latitude,
                    'longitude' => $appInfo->longitude,
                    'logo_color' => Shortcut::fileExistsOnServer($appInfo->logo_color),
                    'logo_white' => Shortcut::fileExistsOnServer($appInfo->logo_white),
                    'address' => $appInfo->address,
                    'town' => $appInfo->town,
                    'country' => $appInfo->country,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('LocalController::getAppInfo error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }
    
    /**
     * Transform product model to API response format
     */
    private function transformProduct(Item $item): array
    {
        // Shortcut::fileExistsOnServer retourne directement l'URL complète
        $image = Shortcut::fileExistsOnServer($item->image);
        
        // Build gallery URLs first
        $images = [];
        if (!empty($item->images) && is_array($item->images)) {
            foreach ($item->images as $img) {
                $resolved = Shortcut::fileExistsOnServer($img);
                $images[] = $resolved;
            }
        }
        
        // Si l'image principale est no-image, chercher dans la galerie
        if (str_contains($image, 'no-image.png') || str_contains($image, 'aucune')) {
            // Chercher une image valide dans la galerie
            $foundValid = false;
            if (!empty($item->images) && is_array($item->images)) {
                foreach ($item->images as $img) {
                    $resolved = Shortcut::fileExistsOnServer($img);
                    if (!str_contains($resolved, 'no-image') && !str_contains($resolved, 'aucune')) {
                        $image = $resolved;
                        $foundValid = true;
                        break;
                    }
                }
            }
            
            // Sinon fallback sur original_image
            if (!$foundValid && !empty($item->original_image)) {
                $image = $item->original_image;
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
            'image' => $image,
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
    private function transformCategory(Category $category): array
    {
        // Shortcut::fileExistsOnServer retourne directement l'URL complète
        $logo = Shortcut::fileExistsOnServer($category->logo);
        
        // Si logo par défaut est no-image, chercher dans local_image d'abord
        if (str_contains($logo, 'no-image.png') || str_contains($logo, 'aucune')) {
            // Priority 1: local_image uploaded by admin
            if (!empty($category->local_image)) {
                $localLogo = Shortcut::fileExistsOnServer($category->local_image);
                if (!str_contains($localLogo, 'no-image')) {
                    $logo = $localLogo;
                }
            }
            // Priority 2: original_logo from HomeIP
            elseif (!empty($category->original_logo)) {
                $logo = $category->original_logo;
            }
        }
        
        return [
            'id' => $category->id,
            'sync_id' => $category->sync_id,
            'name' => $category->name,
            'logo' => $logo,
            'parent_id' => $category->parent_id,
            'items_count' => $category->items_count ?? 0,
        ];
    }
}
