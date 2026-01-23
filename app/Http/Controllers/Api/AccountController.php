<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    /**
     * GET /api/account/dashboard
     * Retourne les statistiques du client connecté
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Account: Acces dashboard client', ['user_id' => $user->id]);
            
            // Nombre de commandes
            $ordersCount = $user->orders()->count();
            
            // Nombre d'articles dans le panier (status = pending, type = cart)
            $cartCount = $user->carts()
                ->where('status', 'pending')
                ->where('type', 'cart')
                ->sum('qty');
            
            // Total dépensé (somme des paiements des commandes du user)
            $totalSpent = Payment::whereHas('order', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->sum('amount');
            
            // Commandes récentes (5 dernières)
            $recentOrders = $user->orders()
                ->with('carts')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'reference' => $order->reference,
                    'date' => $order->created_at->format('d M Y'),
                    'items' => $order->carts->count(),
                    'total' => $order->total,
                    'status' => $order->status,
                ]);

            Log::info('API Account: Dashboard client recupere', [
                'user_id' => $user->id,
                'orders_count' => $ordersCount,
                'cart_count' => $cartCount,
                'total_spent' => $totalSpent
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'orders_count' => $ordersCount,
                    'cart_count' => (int) $cartCount,
                    'total_spent' => (float) $totalSpent,
                    'recent_orders' => $recentOrders,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur dashboard client', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard'
            ], 500);
        }
    }
}
