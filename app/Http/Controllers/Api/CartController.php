<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Get all cart items for the authenticated user
     * Only items with type='cart' and status='pending'
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::debug('API Cart: Recuperation panier', ['user_id' => $user->id]);
            
            $items = Cart::where('user_id', $user->id)
                ->where('type', 'cart')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('API Cart: Panier recupere', ['user_id' => $user->id, 'items_count' => $items->count()]);

            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            Log::error('API Cart: Erreur recuperation panier', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du panier'
            ], 500);
        }
    }

    /**
     * Add item to cart
     * If item already exists, increment quantity
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Cart: Ajout article', [
                'user_id' => $user->id,
                'item_id' => $request->item_id,
                'price' => $request->price
            ]);
            
            $request->validate([
                'item_id' => 'required|string',
                'item_infos' => 'required|array',
                'price' => 'required|numeric|min:0',
            ]);

            // Check if item already exists in cart
            $existingCart = Cart::where('user_id', $user->id)
                ->where('item_id', $request->item_id)
                ->where('type', 'cart')
                ->where('status', 'pending')
                ->first();

            if ($existingCart) {
                // Increment quantity
                $existingCart->qty += 1;
                $existingCart->save();

                Log::info('API Cart: Quantite incrementee', [
                    'user_id' => $user->id,
                    'cart_id' => $existingCart->id,
                    'item_id' => $request->item_id,
                    'new_qty' => $existingCart->qty
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Quantité mise à jour',
                    'data' => $existingCart
                ]);
            }

            // Create new cart item
            $cart = Cart::create([
                'user_id' => $user->id,
                'type' => 'cart',
                'item_id' => $request->item_id,
                'item_infos' => json_encode($request->item_infos),
                'price' => $request->price,
                'qty' => 1,
                'total' => null, // Calculated at checkout only
                'status' => 'pending',
                'order_id' => null,
            ]);

            Log::info('API Cart: Nouvel article ajoute', [
                'user_id' => $user->id,
                'cart_id' => $cart->id,
                'item_id' => $request->item_id,
                'price' => $request->price
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Article ajouté au panier',
                'data' => $cart
            ], 201);
        } catch (\Exception $e) {
            Log::error('API Cart: Erreur ajout article', [
                'user_id' => $request->user()?->id,
                'item_id' => $request->item_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout au panier'
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Cart: Mise a jour quantite', [
                'user_id' => $user->id,
                'cart_id' => $id,
                'new_qty' => $request->qty
            ]);
            
            $request->validate([
                'qty' => 'required|integer|min:0',
            ]);

            $cart = Cart::where('id', $id)
                ->where('user_id', $user->id)
                ->where('type', 'cart')
                ->where('status', 'pending')
                ->first();

            if (!$cart) {
                Log::warning('API Cart: Article non trouve pour mise a jour', [
                    'user_id' => $user->id,
                    'cart_id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé dans le panier'
                ], 404);
            }

            if ((int) $request->qty <= 0) {
                $cart->delete();

                Log::info('API Cart: Article supprime via qty=0', [
                    'user_id' => $user->id,
                    'cart_id' => $id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Article supprimé du panier'
                ]);
            }

            $oldQty = $cart->qty;
            $cart->qty = $request->qty;
            $cart->save();

            Log::info('API Cart: Quantite mise a jour', [
                'user_id' => $user->id,
                'cart_id' => $id,
                'old_qty' => $oldQty,
                'new_qty' => $cart->qty
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quantité mise à jour',
                'data' => $cart
            ]);
        } catch (\Exception $e) {
            Log::error('API Cart: Erreur mise a jour quantite', [
                'user_id' => $request->user()?->id,
                'cart_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Cart: Suppression article', ['user_id' => $user->id, 'cart_id' => $id]);
            
            $cart = Cart::where('id', $id)
                ->where('user_id', $user->id)
                ->where('type', 'cart')
                ->where('status', 'pending')
                ->first();

            if (!$cart) {
                Log::warning('API Cart: Article non trouve pour suppression', [
                    'user_id' => $user->id,
                    'cart_id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Article non trouvé dans le panier'
                ], 404);
            }

            $itemId = $cart->item_id;
            $cart->delete();

            Log::info('API Cart: Article supprime', [
                'user_id' => $user->id,
                'cart_id' => $id,
                'item_id' => $itemId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Article supprimé du panier'
            ]);
        } catch (\Exception $e) {
            Log::error('API Cart: Erreur suppression article', [
                'user_id' => $request->user()?->id,
                'cart_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }

    /**
     * Clear all cart items for the authenticated user
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Cart: Vidage panier', ['user_id' => $user->id]);
            
            $deletedCount = Cart::where('user_id', $user->id)
                ->where('type', 'cart')
                ->where('status', 'pending')
                ->delete();

            Log::info('API Cart: Panier vide', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Panier vidé'
            ]);
        } catch (\Exception $e) {
            Log::error('API Cart: Erreur vidage panier', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du panier'
            ], 500);
        }
    }
}
