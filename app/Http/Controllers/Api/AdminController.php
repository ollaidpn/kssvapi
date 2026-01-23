<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Statistiques globales pour le tableau de bord admin
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            Log::info('API Admin: Acces dashboard', ['admin_id' => $request->user()?->id]);
            
            // Ventes totales (somme des paiements)
            $totalSales = Payment::sum('amount');
            
            // Calcul des variations
            $lastMonthSales = Payment::where('created_at', '>=', now()->subMonth())->sum('amount');
            $previousMonthSales = Payment::whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->sum('amount');
            $salesChange = $previousMonthSales > 0 
                ? round((($lastMonthSales - $previousMonthSales) / $previousMonthSales) * 100, 1) 
                : 0;
            
            // Nombre de commandes
            $ordersCount = Order::count();
            $lastMonthOrders = Order::where('created_at', '>=', now()->subMonth())->count();
            $previousMonthOrders = Order::whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->count();
            $ordersChange = $previousMonthOrders > 0 
                ? round((($lastMonthOrders - $previousMonthOrders) / $previousMonthOrders) * 100, 1) 
                : 0;
            
            // Nombre de clients
            $clientsCount = User::where('account_type', 'client')->count();
            $lastMonthClients = User::where('account_type', 'client')
                ->where('created_at', '>=', now()->subMonth())->count();
            $previousMonthClients = User::where('account_type', 'client')
                ->whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->count();
            $clientsChange = $previousMonthClients > 0 
                ? round((($lastMonthClients - $previousMonthClients) / $previousMonthClients) * 100, 1) 
                : 0;
            
            // Articles vendus
            $itemsSold = Cart::whereNotNull('order_id')->sum('qty');
            $lastMonthItems = Cart::whereNotNull('order_id')
                ->where('created_at', '>=', now()->subMonth())->sum('qty');
            $previousMonthItems = Cart::whereNotNull('order_id')
                ->whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->sum('qty');
            $itemsChange = $previousMonthItems > 0 
                ? round((($lastMonthItems - $previousMonthItems) / $previousMonthItems) * 100, 1) 
                : 0;
            
            // Ventes des 7 derniers jours pour le graphique
            $salesData = [];
            $dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayName = $dayNames[$date->dayOfWeek];
                $daySales = Payment::whereDate('created_at', $date)->sum('amount');
                $salesData[] = [
                    'name' => $dayName,
                    'ventes' => (int) $daySales,
                ];
            }
            
            // 5 commandes récentes
            $recentOrders = Order::with('user:id,name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->reference ?? 'CMD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'client' => $order->user->name ?? 'Inconnu',
                    'amount' => (int) $order->total,
                    'status' => $order->status ?? 'pending',
                ]);
            
            // 3 clients récents
            $recentClients = User::where('account_type', 'client')
                ->latest()
                ->take(3)
                ->get()
                ->map(fn($user) => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'date' => $user->created_at->format('Y-m-d'),
                ]);

            Log::info('API Admin: Dashboard calcule avec succes', [
                'admin_id' => $request->user()?->id,
                'total_sales' => $totalSales,
                'orders_count' => $ordersCount,
                'clients_count' => $clientsCount
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total_sales' => (int) $totalSales,
                        'sales_change' => $salesChange,
                        'orders_count' => $ordersCount,
                        'orders_change' => $ordersChange,
                        'clients_count' => $clientsCount,
                        'clients_change' => $clientsChange,
                        'items_sold' => (int) $itemsSold,
                        'items_change' => $itemsChange,
                    ],
                    'sales_chart' => $salesData,
                    'recent_orders' => $recentOrders,
                    'recent_clients' => $recentClients,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur calcul dashboard', [
                'admin_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard'
            ], 500);
        }
    }

    /**
     * GET /api/admin/clients
     * Liste paginée des clients
     */
    public function clients(Request $request): JsonResponse
    {
        try {
            Log::info('API Admin: Liste clients', [
                'admin_id' => $request->user()?->id,
                'search' => $request->get('search'),
                'page' => $request->get('page', 1)
            ]);
            
            $search = $request->get('search', '');
            $perPage = $request->get('per_page', 20);
            
            $query = User::where('account_type', 'client');
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            $clients = $query->withCount('orders')
                ->latest()
                ->paginate($perPage);
            
            // Calculer total_spent pour chaque client
            $formattedClients = $clients->getCollection()->map(function($client) {
                $totalSpent = Payment::whereHas('order', function($q) use ($client) {
                    $q->where('user_id', $client->id);
                })->sum('amount');
                
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'ccphone' => $client->ccphone,
                    'phone' => $client->phone,
                    'reference' => $client->reference,
                    'orders_count' => $client->orders_count,
                    'total_spent' => (int) $totalSpent,
                    'created_at' => $client->created_at->format('Y-m-d'),
                ];
            });

            Log::info('API Admin: Liste clients recuperee', [
                'admin_id' => $request->user()?->id,
                'total' => $clients->total(),
                'page' => $clients->currentPage()
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedClients,
                'meta' => [
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur liste clients', [
                'admin_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des clients'
            ], 500);
        }
    }

    /**
     * GET /api/admin/clients/{id}
     * Détail complet d'un client avec panier, commandes, paiements
     */
    public function clientDetail(Request $request, int $id): JsonResponse
    {
        try {
            Log::info('API Admin: Detail client', [
                'admin_id' => $request->user()?->id,
                'client_id' => $id
            ]);
            
            $client = User::where('account_type', 'client')->find($id);
            
            if (!$client) {
                Log::warning('API Admin: Client non trouve', [
                    'admin_id' => $request->user()?->id,
                    'client_id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Client non trouvé.',
                ], 404);
            }
            
            // Panier actif (sans order_id)
            $cart = Cart::where('user_id', $id)
                ->whereNull('order_id')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                    'item_infos' => $item->item_infos,
                    'price' => (float) $item->price,
                    'qty' => $item->qty,
                    'total' => (float) $item->total,
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ]);
            
            // Commandes
            $orders = Order::where('user_id', $id)
                ->withCount('carts as items_count')
                ->latest()
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'reference' => $order->reference ?? 'CMD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'items_count' => $order->items_count ?? 0,
                    'total' => (float) $order->total,
                    'status' => $order->status ?? 'pending',
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ]);
            
            // Paiements
            $payments = Payment::whereHas('order', fn($q) => $q->where('user_id', $id))
                ->with('order:id,reference')
                ->latest()
                ->get()
                ->map(fn($payment) => [
                    'id' => $payment->id,
                    'reference' => $payment->reference ?? 'PAY-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
                    'order_reference' => $payment->order->reference ?? 'N/A',
                    'amount' => (float) $payment->amount,
                    'paid_by' => $payment->paid_by ?? 'N/A',
                    'date' => $payment->date ? $payment->date->format('Y-m-d') : null,
                    'created_at' => $payment->created_at->format('Y-m-d H:i'),
                ]);
            
            // Total dépensé
            $totalSpent = Payment::whereHas('order', fn($q) => $q->where('user_id', $id))->sum('amount');

            Log::info('API Admin: Detail client recupere', [
                'admin_id' => $request->user()?->id,
                'client_id' => $id,
                'orders_count' => $orders->count(),
                'cart_count' => $cart->count(),
                'payments_count' => $payments->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'client' => [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'ccphone' => $client->ccphone,
                        'phone' => $client->phone,
                        'reference' => $client->reference,
                        'avatar' => $client->avatar,
                        'created_at' => $client->created_at->format('Y-m-d'),
                    ],
                    'stats' => [
                        'orders_count' => $orders->count(),
                        'cart_count' => $cart->sum('qty'),
                        'total_spent' => (int) $totalSpent,
                    ],
                    'cart' => $cart,
                    'orders' => $orders,
                    'payments' => $payments,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur detail client', [
                'admin_id' => $request->user()?->id,
                'client_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du client'
            ], 500);
        }
    }
}
