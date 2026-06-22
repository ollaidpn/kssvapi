<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyController extends Controller
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('app.kssv_endpoint', 'https://kssvapi.homeip.net/shop'), '/');
    }

    /**
     * Get products list
     * Proxy for /produit
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            $params = [
                'PAGE_START' => $request->get('PAGE_START', 0),
                'PAGE_NBLIGNE' => $request->get('PAGE_NBLIGNE', 50),
            ];

            // Add category filter if provided
            if ($request->has('ID_CATEGORIE')) {
                $params['ID_CATEGORIE'] = $request->get('ID_CATEGORIE');
            }

            Log::info('Proxy: Fetching products', ['params' => $params]);

            $response = Http::timeout(60)->get("{$this->baseUrl}/produit", $params);

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Proxy: Error fetching products', ['error' => $e->getMessage()]);
            return response()->json([
                'OK' => 0,
                'Contenue' => [],
                'error' => 'Failed to fetch products from catalogue API'
            ], 500);
        }
    }

    /**
     * Get single product detail
     * Proxy for /produit/{id}
     */
    public function getProduct($id): JsonResponse
    {
        try {
            Log::info('Proxy: Fetching product detail', ['id' => $id]);

            $response = Http::timeout(30)->get("{$this->baseUrl}/produit/{$id}");

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Proxy: Error fetching product', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'OK' => 0,
                'Contenue' => [],
                'error' => 'Failed to fetch product from catalogue API'
            ], 500);
        }
    }

    /**
     * Search products
     * Proxy for /produit/search/{query}
     */
    public function searchProducts($query, Request $request): JsonResponse
    {
        try {
            Log::info('Proxy: Searching products', ['query' => $query]);

            $response = Http::timeout(30)->get("{$this->baseUrl}/produit/search/{$query}");

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Proxy: Error searching products', ['query' => $query, 'error' => $e->getMessage()]);
            return response()->json([
                'OK' => 0,
                'Contenue' => [],
                'error' => 'Failed to search products from catalogue API'
            ], 500);
        }
    }

    /**
     * Get categories list
     * Proxy for /category
     */
    public function getCategories(Request $request): JsonResponse
    {
        try {
            $params = [
                'PAGE_START' => $request->get('PAGE_START', 0),
                'PAGE_NBLIGNE' => $request->get('PAGE_NBLIGNE', 200),
            ];

            Log::info('Proxy: Fetching categories', ['params' => $params]);

            $response = Http::timeout(30)->get("{$this->baseUrl}/category", $params);

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Proxy: Error fetching categories', ['error' => $e->getMessage()]);
            return response()->json([
                'OK' => 0,
                'Contenue' => [],
                'error' => 'Failed to fetch categories from catalogue API'
            ], 500);
        }
    }

    /**
     * Get single category detail
     * Proxy for /category/{id}
     */
    public function getCategory($id): JsonResponse
    {
        try {
            Log::info('Proxy: Fetching category detail', ['id' => $id]);

            $response = Http::timeout(30)->get("{$this->baseUrl}/category/{$id}");

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Proxy: Error fetching category', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'OK' => 0,
                'Contenue' => [],
                'error' => 'Failed to fetch category from catalogue API'
            ], 500);
        }
    }
}
