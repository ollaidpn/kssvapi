<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Get all cart items for the authenticated user
     * Only items with type='cart' and status='pending'
     */
    public function index(Request $request): JsonResponse
    {
        $items = Cart::where('user_id', $request->user()->id)
            ->where('type', 'cart')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Add item to cart
     * If item already exists, increment quantity
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|string',
            'item_infos' => 'required|array',
            'price' => 'required|numeric|min:0',
        ]);

        // Check if item already exists in cart
        $existingCart = Cart::where('user_id', $request->user()->id)
            ->where('item_id', $request->item_id)
            ->where('type', 'cart')
            ->where('status', 'pending')
            ->first();

        if ($existingCart) {
            // Increment quantity
            $existingCart->qty += 1;
            $existingCart->save();

            return response()->json([
                'success' => true,
                'message' => 'Quantité mise à jour',
                'data' => $existingCart
            ]);
        }

        // Create new cart item
        $cart = Cart::create([
            'user_id' => $request->user()->id,
            'type' => 'cart',
            'item_id' => $request->item_id,
            'item_infos' => json_encode($request->item_infos),
            'price' => $request->price,
            'qty' => 1,
            'total' => null, // Calculated at checkout only
            'status' => 'pending',
            'order_id' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article ajouté au panier',
            'data' => $cart
        ], 201);
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('type', 'cart')
            ->where('status', 'pending')
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier'
            ], 404);
        }

        $cart->qty = $request->qty;
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Quantité mise à jour',
            'data' => $cart
        ]);
    }

    /**
     * Remove item from cart
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $cart = Cart::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('type', 'cart')
            ->where('status', 'pending')
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Article non trouvé dans le panier'
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier'
        ]);
    }

    /**
     * Clear all cart items for the authenticated user
     */
    public function clear(Request $request): JsonResponse
    {
        Cart::where('user_id', $request->user()->id)
            ->where('type', 'cart')
            ->where('status', 'pending')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé'
        ]);
    }
}
