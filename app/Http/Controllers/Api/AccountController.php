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

    /**
     * GET /api/account/orders
     * Retourne la liste complète des commandes du client
     */
    public function orders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Account: Liste commandes client', ['user_id' => $user->id]);
            
            $orders = $user->orders()
                ->withCount('carts as items_count')
                ->latest()
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'reference' => $order->reference,
                    'date' => $order->created_at->format('d M Y'),
                    'items_count' => $order->items_count,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                ]);

            Log::info('API Account: Commandes client recuperees', [
                'user_id' => $user->id,
                'count' => $orders->count()
            ]);
                
            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur liste commandes', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des commandes'
            ], 500);
        }
    }

    /**
     * GET /api/account/orders/{id}
     * Retourne le détail complet d'une commande du client
     */
    public function orderDetail(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Account: Detail commande client', ['user_id' => $user->id, 'order_id' => $id]);
            
            // Vérifier que la commande appartient au client
            $order = $user->orders()
                ->with(['carts', 'payments', 'promoCode'])
                ->find($id);
            
            if (!$order) {
                Log::warning('API Account: Commande non trouvee ou non autorisee', [
                    'user_id' => $user->id,
                    'order_id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }
            
            $items = $order->carts->map(function($cart) {
                $itemInfos = is_string($cart->item_infos) 
                    ? json_decode($cart->item_infos, true) 
                    : $cart->item_infos;
                    
                return [
                    'id' => $cart->id,
                    'item_id' => $cart->item_id,
                    'item_infos' => $itemInfos ?? [],
                    'price' => (float) $cart->price,
                    'qty' => $cart->qty,
                    'total' => (float) $cart->price * $cart->qty,
                ];
            });
            
            $payments = $order->payments->map(fn($p) => [
                'id' => $p->id,
                'reference' => $p->reference,
                'amount' => (float) $p->amount,
                'paid_by' => $p->paid_by,
                'date' => $p->date?->format('d M Y'),
            ]);
            
            $subtotal = $items->sum('total');
            $totalPaid = $payments->sum('amount');
            
            Log::info('API Account: Detail commande recupere', [
                'user_id' => $user->id,
                'order_id' => $id,
                'items_count' => $items->count(),
                'payments_count' => $payments->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'reference' => $order->reference,
                        'status' => $order->status,
                        'discount' => (float) $order->discount,
                        'total' => (float) $order->total,
                        'created_at' => $order->created_at->format('d M Y H:i'),
                    ],
                    'promo_code' => $order->promoCode?->code,
                    'items' => $items,
                    'payments' => $payments,
                    'summary' => [
                        'subtotal' => $subtotal,
                        'discount' => (float) $order->discount,
                        'total' => (float) $order->total,
                        'total_paid' => $totalPaid,
                        'remaining' => (float) $order->total - $totalPaid,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur detail commande', [
                'user_id' => $request->user()?->id,
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du détail de la commande'
            ], 500);
        }
    }

    /**
     * GET /api/account/payments
     * Retourne la liste des paiements du client
     */
    public function payments(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Account: Liste paiements client', ['user_id' => $user->id]);
            
            $payments = Payment::whereHas('order', fn($q) => $q->where('user_id', $user->id))
                ->with('order:id,reference')
                ->latest('date')
                ->get()
                ->map(fn($payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'date' => $payment->date?->format('d M Y'),
                    'order_id' => $payment->order_id,
                    'order_reference' => $payment->order->reference ?? 'N/A',
                    'amount' => (float) $payment->amount,
                    'paid_by' => $payment->paid_by,
                ]);

            Log::info('API Account: Paiements client recuperes', [
                'user_id' => $user->id,
                'count' => $payments->count()
            ]);
                
            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur liste paiements', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des paiements'
            ], 500);
        }
    }
}
