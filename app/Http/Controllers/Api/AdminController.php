<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\Section;
use App\Models\AppInfo;
use App\Models\PromoCode;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Item;
use App\Models\Synchronization;
use App\Helpers\Shortcut;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
            
            // Ventes totales ENCAISSÉES (somme des paiements completed)
            $totalSales = Payment::where('status', 'completed')->sum('amount');
            
            // Calcul des variations
            $lastMonthSales = Payment::where('status', 'completed')->where('created_at', '>=', now()->subMonth())->sum('amount');
            $previousMonthSales = Payment::where('status', 'completed')->whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->sum('amount');
            $salesChange = $previousMonthSales > 0 
                ? round((($lastMonthSales - $previousMonthSales) / $previousMonthSales) * 100, 1) 
                : 0;
            
            // Nombre de commandes EN COURS (status = processing)
            $ordersCount = Order::where('status', 'processing')->count();
            $lastMonthOrders = Order::where('status', 'processing')->where('created_at', '>=', now()->subMonth())->count();
            $previousMonthOrders = Order::where('status', 'processing')->whereBetween('created_at', [now()->subMonths(2), now()->subMonth()])->count();
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
            
            // Articles vendus (commandes PAYÉES)
            $itemsSold = Cart::whereHas('order', fn($q) => $q->where('payment_status', 'paid'))->sum('qty');
            $lastMonthItems = Cart::whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
                ->where('created_at', '>=', now()->subMonth())->sum('qty');
            $previousMonthItems = Cart::whereHas('order', fn($q) => $q->where('payment_status', 'paid'))
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
                        'avatar' => Shortcut::fileExistsOnServer($client->avatar),
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

    // ============================================
    // Orders Management
    // ============================================

    /**
     * GET /api/admin/orders
     * Récupère la liste des commandes avec filtres
     */
    public function getOrders(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $status = $request->get('status', 'all');
            $perPage = $request->get('per_page', 20);
            
            Log::info('API Admin: Chargement liste commandes', [
                'admin_id' => $request->user()?->id,
                'search' => $search, 
                'status' => $status
            ]);
            
            $query = Order::with('user:id,name,email')
                ->withCount('carts as items_count');
            
            // Filtre par recherche (reference ou nom client)
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
                });
            }
            
            // Filtre par statut
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            
            $orders = $query->latest()->paginate($perPage);
            
            // Compter par statut pour les badges
            $statusCounts = [
                'all' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'pending_payment' => Order::where('status', 'pending_payment')->count(),
                'processing' => Order::where('status', 'processing')->count(),
                'paid' => Order::where('status', 'paid')->count(),
                'completed' => Order::where('status', 'completed')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ];
            
            $formattedOrders = $orders->map(fn($order) => [
                'id' => $order->id,
                'reference' => $order->reference ?? 'CMD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'client_name' => $order->user->name ?? 'Inconnu',
                'client_email' => $order->user->email ?? '',
                'items_count' => $order->items_count ?? 0,
                'discount' => (float) $order->discount,
                'total' => (float) $order->total,
                'status' => $order->status ?? 'pending',
                'created_at' => $order->created_at->format('Y-m-d H:i'),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'status_counts' => $statusCounts,
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur liste commandes', [
                'admin_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/admin/orders/{id}
     * Récupère le détail complet d'une commande
     */
    public function getOrderDetail(Request $request, int $id): JsonResponse
    {
        try {
            Log::info('API Admin: Detail commande', [
                'admin_id' => $request->user()?->id,
                'order_id' => $id
            ]);
            
            $order = Order::with(['user:id,name,email,phone,ccphone', 'carts', 'payments', 'promoCode'])
                ->findOrFail($id);
            
            // Articles de la commande
            $items = $order->carts->map(fn($cart) => [
                'id' => $cart->id,
                'item_id' => $cart->item_id,
                'item_infos' => $cart->item_infos,
                'price' => (float) $cart->price,
                'qty' => $cart->qty,
                'total' => (float) $cart->total,
            ]);
            
            // Paiements
            $payments = $order->payments->map(fn($payment) => [
                'id' => $payment->id,
                'reference' => $payment->reference ?? 'PAY-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT),
                'amount' => (float) $payment->amount,
                'paid_by' => $payment->paid_by ?? 'N/A',
                'payment_type' => $payment->payment_type ?? 'online',
                'date' => $payment->date?->format('Y-m-d'),
                'created_at' => $payment->created_at->format('Y-m-d H:i'),
            ]);
            
            // Calculs
            $subtotal = $items->sum('total');
            $totalPaid = $payments->sum('amount');
            $remaining = $order->total - $totalPaid;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'reference' => $order->reference ?? 'CMD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                        'status' => $order->status ?? 'pending',
                        'delivery_status' => $order->delivery_status ?? 'pending',
                        'payment_status' => $order->payment_status ?? 'pending',
                        'payment_method' => $order->payment_method ?? 'cash_on_delivery',
                        'address' => $order->address,
                        'city' => $order->city,
                        'discount' => (float) $order->discount,
                        'total' => (float) $order->total,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                        'updated_at' => $order->updated_at->format('Y-m-d H:i'),
                    ],
                    'client' => [
                        'id' => $order->user->id ?? null,
                        'name' => $order->user->name ?? 'Inconnu',
                        'email' => $order->user->email ?? '',
                        'phone' => ($order->user->ccphone ?? '') . ' ' . ($order->user->phone ?? ''),
                    ],
                    'promo_code' => $order->promoCode ? [
                        'code' => $order->promoCode->code ?? null,
                        'value' => $order->promoCode->value ?? null,
                    ] : null,
                    'items' => $items,
                    'payments' => $payments,
                    'summary' => [
                        'subtotal' => $subtotal,
                        'discount' => (float) $order->discount,
                        'total' => (float) $order->total,
                        'total_paid' => $totalPaid,
                        'remaining' => $remaining,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur detail commande', [
                'admin_id' => $request->user()?->id,
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/admin/orders/{id}/status
     * Change le statut d'une commande
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|in:general,delivery,payment',
                'status' => 'required|string',
                'paid_by' => 'nullable|string|in:cash,wave,orange_money,cheque,bank_transfer',
            ]);
            
            $order = Order::findOrFail($id);
            $type = $validated['type'];
            $newStatus = $validated['status'];
            
            Log::info('API Admin: Changement statut commande', [
                'order_id' => $id,
                'type' => $type,
                'new_status' => $newStatus,
                'admin_id' => $request->user()?->id,
            ]);
            
            switch ($type) {
                case 'general':
                    // Validation des statuts généraux
                    if (!in_array($newStatus, ['pending', 'pending_payment', 'processing', 'paid', 'completed', 'delivered', 'cancelled'])) {
                        return response()->json(['success' => false, 'message' => 'Statut général invalide'], 422);
                    }
                    $order->status = $newStatus;
                    break;
                    
                case 'delivery':
                    // Validation des statuts de livraison
                    if (!in_array($newStatus, ['pending', 'processing', 'delivered'])) {
                        return response()->json(['success' => false, 'message' => 'Statut de livraison invalide'], 422);
                    }
                    $order->delivery_status = $newStatus;
                    break;
                    
                case 'payment':
                    // Vérifier si des paiements online existent (non modifiables)
                    $hasOnlinePayment = $order->payments()->where('payment_type', 'online')->exists();
                    if ($hasOnlinePayment) {
                        return response()->json([
                            'success' => false, 
                            'message' => 'Impossible de modifier le statut d\'un paiement effectué en ligne'
                        ], 422);
                    }
                    
                    if ($newStatus === 'paid') {
                        // Créer un paiement manuel
                        $paidBy = $validated['paid_by'] ?? 'cash';
                        $remainingAmount = $order->total - $order->payments()->sum('amount');
                        
                        if ($remainingAmount > 0) {
                            Payment::create([
                                'order_id' => $order->id,
                                'amount' => $remainingAmount,
                                'paid_by' => $paidBy,
                                'payment_type' => 'manual',
                                'reference' => 'PAY-' . strtoupper(\Str::random(8)),
                                'date' => now(),
                                'status' => 'completed',
                            ]);
                        }
                        $order->payment_status = 'paid';
                    } elseif ($newStatus === 'pending') {
                        // Supprimer uniquement les paiements manuels
                        $order->payments()->where('payment_type', 'manual')->delete();
                        $order->payment_status = 'pending';
                    }
                    break;
            }
            
            // Auto-complete: Si payé ET livré → statut = completed
            if ($order->payment_status === 'paid' && $order->delivery_status === 'delivered') {
                $order->status = 'completed';
            }
            
            $order->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => [
                    'id' => $order->id,
                    'reference' => $order->reference ?? 'CMD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'status' => $order->status,
                    'delivery_status' => $order->delivery_status,
                    'payment_status' => $order->payment_status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur changement statut', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Payments Management
    // ============================================

    /**
     * GET /api/admin/payments
     * Liste tous les paiements avec filtres
     */
    public function getPayments(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'method' => 'nullable|string|in:orange_money,wave,card,cash,all',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);
            
            Log::info('API Admin: Liste des paiements', [
                'admin_id' => $request->user()?->id,
                'filters' => $validated
            ]);
            
            $query = Payment::with(['order.user:id,name']);
            
            // Filtre de recherche
            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhereHas('order', fn($oq) => 
                          $oq->where('reference', 'like', "%{$search}%")
                             ->orWhereHas('user', fn($uq) => 
                                 $uq->where('name', 'like', "%{$search}%")
                             )
                      );
                });
            }
            
            // Filtre par méthode de paiement
            if (!empty($validated['method']) && $validated['method'] !== 'all') {
                $query->where('paid_by', $validated['method']);
            }
            
            // Pagination
            $perPage = $validated['per_page'] ?? 20;
            $payments = $query->latest('date')->paginate($perPage);
            
            // Total encaissé (tous les paiements, pas filtrés)
            $totalCompleted = Payment::sum('amount');
            
            $formattedPayments = collect($payments->items())->map(fn($p) => [
                'id' => $p->id,
                'reference' => $p->reference ?? 'PAY-' . str_pad($p->id, 4, '0', STR_PAD_LEFT),
                'order_id' => $p->order_id,
                'order_ref' => $p->order?->reference ?? 'N/A',
                'client' => $p->order?->user?->name ?? 'Inconnu',
                'amount' => (float) $p->amount,
                'method' => $p->paid_by ?? 'cash',
                'status' => 'completed',
                'date' => $p->date?->format('Y-m-d H:i'),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $formattedPayments,
                    'total_completed' => (float) $totalCompleted,
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                    ],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('API Admin: Validation error payments', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur liste paiements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paiements',
            ], 500);
        }
    }

    // ============================================
    // Sections Management
    // ============================================

    /**
     * GET /api/admin/sections
     * Récupère les sections par types
     */
    public function getSections(Request $request): JsonResponse
    {
        try {
            $types = $request->get('types', '');
            $typesArray = array_filter(explode(',', $types));
            
            Log::info('API Admin: Chargement sections', ['types' => $typesArray]);
            
            $query = Section::query();
            if (!empty($typesArray)) {
                $query->whereIn('type', $typesArray);
            }
            
            $sections = $query->orderBy('type')->orderBy('id')->get()->map(function($section) {
                $section->image1 = Shortcut::fileExistsOnServer($section->image1);
                $section->image2 = Shortcut::fileExistsOnServer($section->image2);
                return $section;
            });
            
            return response()->json([
                'success' => true,
                'data' => $sections
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur chargement sections', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur'], 500);
        }
    }

    /**
     * POST /api/admin/sections/{id}
     * Met à jour une section avec support upload image
     */
    public function updateSection(Request $request, int $id): JsonResponse
    {
        try {
            $section = Section::findOrFail($id);
            
            Log::info('API Admin: Update section', ['section_id' => $id, 'type' => $section->type]);
            
            // Champs texte
            $section->fill($request->only(['title', 'subtitle', 'description', 'btn', 'link']));
            
            // Upload image1
            if ($request->hasFile('image1')) {
                $fileName = 'section-' . $section->id . '-img1-' . time() . '.' . $request->file('image1')->getClientOriginalExtension();
                $path = $request->file('image1')->storeAs('public/sections', $fileName);
                if ($path) {
                    $section->image1 = 'storage/sections/' . $fileName;
                }
            }
            
            // Upload image2 (seulement pour slider)
            if ($request->hasFile('image2') && $section->type === 'slider') {
                $fileName = 'section-' . $section->id . '-img2-' . time() . '.' . $request->file('image2')->getClientOriginalExtension();
                $path = $request->file('image2')->storeAs('public/sections', $fileName);
                if ($path) {
                    $section->image2 = 'storage/sections/' . $fileName;
                }
            }
            
            $section->save();
            
            // Transformer les URLs des images avant de retourner
            $section->image1 = Shortcut::fileExistsOnServer($section->image1);
            $section->image2 = Shortcut::fileExistsOnServer($section->image2);
            
            Log::info('API Admin: Section mise à jour', ['section_id' => $id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Section mise à jour',
                'data' => $section
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur update section', [
                'section_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/sections
     * Crée une nouvelle section (seulement slider)
     */
    public function createSection(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|in:slider',
                'title' => 'nullable|string',
                'subtitle' => 'nullable|string',
                'description' => 'nullable|string',
                'btn' => 'nullable|string',
                'link' => 'nullable|string',
            ]);
            
            Log::info('API Admin: Création section', ['type' => $validated['type']]);
            
            $section = Section::create($validated);
            
            // Upload images si présentes
            if ($request->hasFile('image1')) {
                $fileName = 'section-' . $section->id . '-img1-' . time() . '.' . $request->file('image1')->getClientOriginalExtension();
                $path = $request->file('image1')->storeAs('public/sections', $fileName);
                if ($path) {
                    $section->image1 = 'storage/sections/' . $fileName;
                }
            }
            if ($request->hasFile('image2')) {
                $fileName = 'section-' . $section->id . '-img2-' . time() . '.' . $request->file('image2')->getClientOriginalExtension();
                $path = $request->file('image2')->storeAs('public/sections', $fileName);
                if ($path) {
                    $section->image2 = 'storage/sections/' . $fileName;
                }
            }
            $section->save();
            
            // Transformer les URLs des images avant de retourner
            $section->image1 = Shortcut::fileExistsOnServer($section->image1);
            $section->image2 = Shortcut::fileExistsOnServer($section->image2);
            
            Log::info('API Admin: Section créée', ['section_id' => $section->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Section créée',
                'data' => $section
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur création section', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/sections/{id}
     * Supprime une section (seulement slider)
     */
    public function deleteSection(int $id): JsonResponse
    {
        try {
            $section = Section::findOrFail($id);
            
            // Vérifier que c'est un slider (hero et ads ne peuvent pas être supprimés)
            if ($section->type !== 'slider') {
                Log::warning('API Admin: Tentative suppression section non-slider', [
                    'section_id' => $id,
                    'type' => $section->type
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Les sections Hero et Ads ne peuvent pas être supprimées'
                ], 403);
            }
            
            Log::info('API Admin: Suppression section slider', ['section_id' => $id]);
            $section->delete();
            
            return response()->json(['success' => true, 'message' => 'Section supprimée']);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur suppression section', [
                'section_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // AppInfo Management
    // ============================================

    /**
     * GET /api/admin/app-info
     * Récupère les infos de l'application
     */
    public function getAppInfo(): JsonResponse
    {
        try {
            Log::debug('API Admin: Chargement AppInfo');
            
            $appInfo = AppInfo::first();
            
            // Transformer les URLs des logos avant de retourner
            if ($appInfo) {
                $appInfo->logo_color = Shortcut::fileExistsOnServer($appInfo->logo_color);
                $appInfo->logo_white = Shortcut::fileExistsOnServer($appInfo->logo_white);
            }
            
            return response()->json([
                'success' => true,
                'data' => $appInfo
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur chargement AppInfo', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/app-info
     * Met à jour les infos de l'application
     */
    public function updateAppInfo(Request $request): JsonResponse
    {
        try {
            $appInfo = AppInfo::first();
            if (!$appInfo) {
                $appInfo = new AppInfo();
            }
            
            Log::info('API Admin: Update AppInfo');
            
            // Champs texte
            $appInfo->fill($request->only([
                'name', 'ccphone1', 'phone1', 'ccphone2', 'phone2',
                'email1', 'email2', 'latitude', 'longitude',
                'address', 'town', 'country', 'maintenance', 'show_only_with_images'
            ]));
            
            // Upload logo_color
            if ($request->hasFile('logo_color')) {
                $fileName = 'logo-color-' . time() . '.' . $request->file('logo_color')->getClientOriginalExtension();
                $path = $request->file('logo_color')->storeAs('public/logos', $fileName);
                if ($path) {
                    $appInfo->logo_color = 'storage/logos/' . $fileName;
                }
            }
            
            // Upload logo_white
            if ($request->hasFile('logo_white')) {
                $fileName = 'logo-white-' . time() . '.' . $request->file('logo_white')->getClientOriginalExtension();
                $path = $request->file('logo_white')->storeAs('public/logos', $fileName);
                if ($path) {
                    $appInfo->logo_white = 'storage/logos/' . $fileName;
                }
            }
            
            $appInfo->save();
            
            // Transformer les URLs des logos avant de retourner
            $appInfo->logo_color = Shortcut::fileExistsOnServer($appInfo->logo_color);
            $appInfo->logo_white = Shortcut::fileExistsOnServer($appInfo->logo_white);
            
            Log::info('API Admin: AppInfo mise à jour', ['app_info_id' => $appInfo->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Informations mises à jour',
                'data' => $appInfo
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur update AppInfo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/app-info/toggle-image-filter
     * Toggle le filtre des images
     */
    public function toggleImageFilter(Request $request): JsonResponse
    {
        try {
            $appInfo = AppInfo::first();
            if (!$appInfo) {
                $appInfo = AppInfo::create(['name' => 'KSSV', 'show_only_with_images' => false]);
            }
            
            $appInfo->update([
                'show_only_with_images' => $request->boolean('enabled')
            ]);
            
            Log::info('API Admin: Toggle image filter', [
                'enabled' => $appInfo->show_only_with_images
            ]);
            
            return response()->json([
                'success' => true,
                'show_only_with_images' => $appInfo->show_only_with_images,
                'message' => $appInfo->show_only_with_images 
                    ? 'Seuls les articles avec image seront affichés'
                    : 'Tous les articles seront affichés'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur toggle image filter', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Promo Codes Management
    // ============================================

    /**
     * GET /api/admin/promo-codes
     * Liste des codes promo
     */
    public function getPromoCodes(Request $request): JsonResponse
    {
        try {
            Log::info('API Admin: Liste codes promo', ['admin_id' => $request->user()?->id]);
            
            $promoCodes = PromoCode::latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => $promoCodes
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur liste codes promo', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/promo-codes
     * Créer un code promo
     */
    public function createPromoCode(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|unique:promo_codes,code',
                'discount_by' => 'required|string|in:amount,percent',
                'discount_value' => 'required|numeric|min:0',
                'filter_by' => 'nullable|string',
                'limite' => 'nullable|integer',
                'expiration' => 'nullable|date',
                'status' => 'nullable|string|in:active,inactive',
                'message' => 'nullable|string',
            ]);
            
            Log::info('API Admin: Création code promo', [
                'admin_id' => $request->user()?->id,
                'code' => $validated['code']
            ]);
            
            $promoCode = PromoCode::create([
                'code' => strtoupper($validated['code']),
                'discount_by' => $validated['discount_by'],
                'discount_value' => $validated['discount_value'],
                'filter_by' => $validated['filter_by'] ?? 'date',
                'limite' => $validated['limite'] ?? -1,
                'expiration' => $validated['expiration'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'message' => $validated['message'] ?? null,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Code promo créé',
                'data' => $promoCode
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ce code existe déjà',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur création code promo', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/admin/promo-codes/{id}
     * Modifier un code promo
     */
    public function updatePromoCode(Request $request, int $id): JsonResponse
    {
        try {
            $promoCode = PromoCode::find($id);
            
            if (!$promoCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code promo non trouvé'
                ], 404);
            }
            
            $validated = $request->validate([
                'code' => 'sometimes|string|unique:promo_codes,code,' . $id,
                'discount_by' => 'sometimes|string|in:amount,percent',
                'discount_value' => 'sometimes|numeric|min:0',
                'filter_by' => 'nullable|string',
                'limite' => 'nullable|integer',
                'expiration' => 'nullable|date',
                'status' => 'nullable|string|in:active,inactive',
                'message' => 'nullable|string',
            ]);
            
            Log::info('API Admin: Modification code promo', [
                'admin_id' => $request->user()?->id,
                'promo_id' => $id
            ]);
            
            if (isset($validated['code'])) {
                $validated['code'] = strtoupper($validated['code']);
            }
            
            $promoCode->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Code promo modifié',
                'data' => $promoCode->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur modification code promo', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/promo-codes/{id}
     * Supprimer un code promo
     */
    public function deletePromoCode(int $id): JsonResponse
    {
        try {
            $promoCode = PromoCode::find($id);
            
            if (!$promoCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code promo non trouvé'
                ], 404);
            }
            
            // Vérifier si le code est utilisé
            $usageCount = Order::where('promo_code_id', $id)->count();
            if ($usageCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Ce code a été utilisé $usageCount fois et ne peut pas être supprimé"
                ], 400);
            }
            
            Log::info('API Admin: Suppression code promo', ['promo_id' => $id]);
            
            $promoCode->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Code promo supprimé'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur suppression code promo', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Local Categories Management
    // ============================================

    /**
     * GET /api/admin/categories
     * Liste des catégories
     */
    public function getCategories(Request $request): JsonResponse
    {
        try {
            $categories = Category::withCount('items')
                ->orderBy('name')
                ->get()
                ->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'logo' => $cat->logo ? Shortcut::fileExistsOnServer($cat->logo) : null,
                        'parent_id' => $cat->parent_id,
                        'items_count' => $cat->items_count,
                        'created_at' => $cat->created_at?->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json(['success' => true, 'data' => $categories]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur récupération catégories', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/categories
     * Créer une catégorie
     */
    public function createCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'logo' => 'nullable|string',
                'parent_id' => 'nullable|integer|exists:categories,id',
            ]);

            $category = Category::create($validated);

            Log::info('API Admin: Catégorie créée', ['category_id' => $category->id]);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Catégorie créée avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur création catégorie', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/admin/categories/{id}
     * Modifier une catégorie
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['success' => false, 'message' => 'Catégorie non trouvée'], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'logo' => 'nullable|string',
                'parent_id' => 'nullable|integer|exists:categories,id',
            ]);

            // Éviter auto-référence
            if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
                return response()->json(['success' => false, 'message' => 'Une catégorie ne peut pas être son propre parent'], 400);
            }

            $category->update($validated);

            Log::info('API Admin: Catégorie modifiée', ['category_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Catégorie modifiée avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur modification catégorie', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/categories/{id}
     * Supprimer une catégorie
     */
    public function deleteCategory(int $id): JsonResponse
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['success' => false, 'message' => 'Catégorie non trouvée'], 404);
            }

            Log::info('API Admin: Suppression catégorie', ['category_id' => $id]);
            $category->delete();

            return response()->json(['success' => true, 'message' => 'Catégorie supprimée']);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur suppression catégorie', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Brands Management
    // ============================================

    /**
     * GET /api/admin/brands
     * Liste des marques
     */
    public function getBrands(Request $request): JsonResponse
    {
        try {
            $brands = Brand::withCount('items')
                ->orderBy('name')
                ->get()
                ->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'logo' => $brand->logo ? Shortcut::fileExistsOnServer($brand->logo) : null,
                        'items_count' => $brand->items_count,
                        'created_at' => $brand->created_at?->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json(['success' => true, 'data' => $brands]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur récupération marques', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/brands
     * Créer une marque
     */
    public function createBrand(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'logo' => 'nullable|string',
            ]);

            $brand = Brand::create($validated);

            Log::info('API Admin: Marque créée', ['brand_id' => $brand->id]);

            return response()->json([
                'success' => true,
                'data' => $brand,
                'message' => 'Marque créée avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur création marque', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/admin/brands/{id}
     * Modifier une marque
     */
    public function updateBrand(Request $request, int $id): JsonResponse
    {
        try {
            $brand = Brand::find($id);
            if (!$brand) {
                return response()->json(['success' => false, 'message' => 'Marque non trouvée'], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'logo' => 'nullable|string',
            ]);

            $brand->update($validated);

            Log::info('API Admin: Marque modifiée', ['brand_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $brand,
                'message' => 'Marque modifiée avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur modification marque', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/brands/{id}
     * Supprimer une marque
     */
    public function deleteBrand(int $id): JsonResponse
    {
        try {
            $brand = Brand::find($id);
            if (!$brand) {
                return response()->json(['success' => false, 'message' => 'Marque non trouvée'], 404);
            }

            Log::info('API Admin: Suppression marque', ['brand_id' => $id]);
            $brand->delete();

            return response()->json(['success' => true, 'message' => 'Marque supprimée']);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur suppression marque', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Items (Inventory) Management
    // ============================================

    /**
     * GET /api/admin/items
     * Liste des articles avec pagination et filtres
     */
    public function getItems(Request $request): JsonResponse
    {
        try {
            $query = Item::with(['category:id,name', 'brand:id,name']);

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filtres
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = $request->input('per_page', 20);
            $items = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $data = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => (float) $item->price,
                    'sale_price' => $item->sale_price ? (float) $item->sale_price : null,
                    'category_id' => $item->category_id,
                    'brand_id' => $item->brand_id,
                    'category' => $item->category ? ['id' => $item->category->id, 'name' => $item->category->name] : null,
                    'brand' => $item->brand ? ['id' => $item->brand->id, 'name' => $item->brand->name] : null,
                    'image' => $item->image ? Shortcut::fileExistsOnServer($item->image) : null,
                    'images' => $item->images,
                    'status' => $item->status,
                    'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur récupération articles', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/items
     * Créer un article
     */
    public function createItem(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'category_id' => 'nullable|integer|exists:local_categories,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'image' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'string',
                'status' => 'required|string|in:active,draft,archived',
            ]);

            $item = Item::create($validated);

            Log::info('API Admin: Article créé', ['item_id' => $item->id]);

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Article créé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur création article', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/admin/items/{id}
     * Modifier un article
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        try {
            $item = Item::find($id);
            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Article non trouvé'], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'category_id' => 'nullable|integer|exists:local_categories,id',
                'brand_id' => 'nullable|integer|exists:brands,id',
                'image' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'string',
                'status' => 'required|string|in:active,draft,archived',
            ]);

            $item->update($validated);

            Log::info('API Admin: Article modifié', ['item_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $item->fresh(),
                'message' => 'Article modifié avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur modification article', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/items/{id}
     * Supprimer un article
     */
    public function deleteItem(int $id): JsonResponse
    {
        try {
            $item = Item::find($id);
            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Article non trouvé'], 404);
            }

            Log::info('API Admin: Suppression article', ['item_id' => $id]);
            $item->delete();

            return response()->json(['success' => true, 'message' => 'Article supprimé']);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur suppression article', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Synchronization Management
    // ============================================

    /**
     * GET /api/admin/synchronizations
     * Liste paginée des entrées de synchronisation avec statistiques
     */
    public function getSynchronizations(Request $request): JsonResponse
    {
        try {
            Log::info('API Admin: Chargement synchronizations', [
                'admin_id' => $request->user()?->id,
                'type' => $request->get('type'),
                'status' => $request->get('status')
            ]);

            $query = Synchronization::query();

            // Filtres
            if ($request->type && $request->type !== 'all') {
                $query->where('type', $request->type);
            }
            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('type_id', 'like', "%{$request->search}%")
                      ->orWhere('data', 'like', "%{$request->search}%");
                });
            }

            // Stats globales
            $stats = [
                'total' => Synchronization::count(),
                'unsync' => Synchronization::where('status', 'unsync')->count(),
                'synced' => Synchronization::where('status', 'synced')->count(),
                'changed' => Synchronization::where('status', 'changed')->count(),
                'error' => Synchronization::where('status', 'error')->count(),
                'items' => Synchronization::where('type', 'item')->count(),
                'categories' => Synchronization::where('type', 'category')->count(),
            ];

            $perPage = $request->get('per_page', 50);
            $synchronizations = $query->orderBy('updated_at', 'desc')->paginate($perPage);

            // Formater pour le frontend
            $formatted = $synchronizations->getCollection()->map(function($sync) {
                $name = 'Sans nom';
                if ($sync->type === 'item') {
                    $name = $sync->data['nom'] ?? $sync->data['libelle'] ?? 'Sans nom';
                } elseif ($sync->type === 'category') {
                    $name = $sync->data['nom'] ?? 'Sans nom';
                }

                return [
                    'id' => $sync->id,
                    'type' => $sync->type,
                    'type_id' => $sync->type_id,
                    'name' => $name,
                    'status' => $sync->status,
                    'data' => $sync->data,
                    'created_at' => $sync->created_at?->format('Y-m-d H:i'),
                    'updated_at' => $sync->updated_at?->format('Y-m-d H:i'),
                    'last_sync_at' => $sync->last_sync_at?->format('Y-m-d H:i'),
                    'last_updated_at' => $sync->last_updated_at?->format('Y-m-d H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'stats' => $stats,
                'meta' => [
                    'current_page' => $synchronizations->currentPage(),
                    'last_page' => $synchronizations->lastPage(),
                    'per_page' => $synchronizations->perPage(),
                    'total' => $synchronizations->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur liste synchronizations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/synchronizations/run
     * Lance la synchronisation manuelle depuis l'API HomeIP
     */
    public function runSync(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'all'); // 'all', 'items', 'categories'
            $results = ['items' => 0, 'categories' => 0, 'errors' => []];

            Log::info('API Admin: Lancement synchronisation', [
                'admin_id' => $request->user()?->id,
                'type' => $type
            ]);

            if ($type === 'all' || $type === 'items') {
                $results['items'] = $this->syncItems($results['errors']);
            }
            if ($type === 'all' || $type === 'categories') {
                $results['categories'] = $this->syncCategories($results['errors']);
            }

            Log::info('API Admin: Synchronisation terminée', $results);

            return response()->json([
                'success' => true,
                'message' => 'Synchronisation terminée',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur synchronisation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Synchronise les articles depuis HomeIP
     */
    private function syncItems(array &$errors): int
    {
        $count = 0;
        $pageStart = 0;
        $pageSize = 200;
        $maxPages = 50; // Limite pour éviter boucle infinie

        for ($page = 0; $page < $maxPages; $page++) {
            try {
                $response = Http::timeout(30)->get("https://kssvapi.homeip.net/shop/produit", [
                    'PAGE_START' => $pageStart,
                    'PAGE_NBLIGNE' => $pageSize
                ]);

                if (!$response->successful()) {
                    $errors[] = "Erreur HTTP items page $page: " . $response->status();
                    break;
                }

                $data = $response->json();

                if (!isset($data['OK']) || $data['OK'] !== 1) {
                    $errors[] = "API items retourne OK=0 à la page $page";
                    break;
                }

                // Gestion du cas où Contenue est un array vide
                if (!isset($data['Contenue']) || is_array($data['Contenue']) && empty($data['Contenue'])) {
                    break;
                }

                $liste = $data['Contenue']['liste'] ?? [];
                if (empty($liste)) {
                    break;
                }

                foreach ($liste as $item) {
                    if (!isset($item['id'])) continue;
                    $this->upsertSync('item', (string)$item['id'], $item);
                    $count++;
                }

                // Si on a moins d'éléments que demandé, on a tout récupéré
                if (count($liste) < $pageSize) {
                    break;
                }

                $pageStart += $pageSize;

            } catch (\Exception $e) {
                $errors[] = "Exception items page $page: " . $e->getMessage();
                break;
            }
        }

        return $count;
    }

    /**
     * Synchronise les catégories depuis HomeIP
     */
    private function syncCategories(array &$errors): int
    {
        $count = 0;

        try {
            $response = Http::timeout(30)->get("https://kssvapi.homeip.net/shop/category", [
                'PAGE_START' => 0,
                'PAGE_NBLIGNE' => 500
            ]);

            if (!$response->successful()) {
                $errors[] = "Erreur HTTP categories: " . $response->status();
                return 0;
            }

            $data = $response->json();

            if (!isset($data['OK']) || $data['OK'] !== 1) {
                $errors[] = "API categories retourne OK=0";
                return 0;
            }

            // Gestion du cas où Contenue est un array vide
            if (!isset($data['Contenue']) || is_array($data['Contenue']) && empty($data['Contenue'])) {
                return 0;
            }

            $liste = $data['Contenue']['liste'] ?? [];

            foreach ($liste as $category) {
                if (!isset($category['id'])) continue;
                $this->upsertSync('category', (string)$category['id'], $category);
                $count++;
            }

        } catch (\Exception $e) {
            $errors[] = "Exception categories: " . $e->getMessage();
        }

        return $count;
    }

    /**
     * Upsert une entrée de synchronisation
     */
    private function upsertSync(string $type, string $typeId, array $data): void
    {
        $existing = Synchronization::where('type', $type)
            ->where('type_id', $typeId)
            ->first();

        if ($existing) {
            // Comparer les données pour détecter les changements
            $oldDataJson = json_encode($existing->data);
            $newDataJson = json_encode($data);
            $hasChanged = $oldDataJson !== $newDataJson;

            $existing->update([
                'data' => $data,
                'status' => $hasChanged && $existing->status === 'synced' ? 'changed' : $existing->status,
                'last_sync_at' => now(),
            ]);
        } else {
            Synchronization::create([
                'type' => $type,
                'type_id' => $typeId,
                'data' => $data,
                'status' => 'unsync',
                'last_sync_at' => now(),
            ]);
        }
    }

    /**
     * POST /api/admin/synchronizations/apply
     * Applique les entrées sélectionnées vers les tables locales
     */
    public function applySyncToLocal(Request $request): JsonResponse
    {
        try {
            $ids = $request->get('ids', []);
            $applied = 0;
            $errors = [];

            Log::info('API Admin: Application sync vers local', [
                'admin_id' => $request->user()?->id,
                'ids_count' => count($ids)
            ]);

            foreach ($ids as $id) {
                $sync = Synchronization::find($id);
                if (!$sync) continue;

                try {
                    if ($sync->type === 'item') {
                        $this->applyItemToLocal($sync);
                    } elseif ($sync->type === 'category') {
                        $this->applyCategoryToLocal($sync);
                    }

                    $sync->update(['status' => 'synced']);
                    $applied++;
                } catch (\Exception $e) {
                    $errors[] = "Erreur ID {$id}: " . $e->getMessage();
                    $sync->update(['status' => 'error']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "$applied élément(s) synchronisé(s)",
                'applied' => $applied,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur application sync', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Applique un item synchronisé vers la table locale Item
     */
    private function applyItemToLocal(Synchronization $sync): void
    {
        $data = $sync->data;
        $imageService = new \App\Services\ImageDownloadService();

        // L'ID original de HomeIP (type_id dans synchronizations)
        $ogId = $sync->type_id;

        // Trouver la catégorie locale via og_id
        $categoryId = null;
        if (!empty($data['id_categorie'])) {
            // Chercher la catégorie locale par son og_id (plus robuste)
            $localCat = Category::where('og_id', (string)$data['id_categorie'])->first();
            if ($localCat) {
                $categoryId = $localCat->id;
            }
        }

        // Télécharger l'image principale (retourne null si placeholder)
        $originalImage = $data['photo'] ?? null;
        $localImage = null;
        if ($originalImage) {
            $localImage = $imageService->downloadImage($originalImage, 'items');
        }
        
        // Si pas d'image locale valide, utiliser no-image.png
        $finalImage = $localImage ?? 'no-image.png';

        // Télécharger la galerie d'images
        $gallery = $data['GALLERIE'] ?? [];
        $localImages = [];
        if (!empty($gallery) && is_array($gallery)) {
            $localImages = $imageService->downloadImages($gallery, 'items/gallery');
        }

        // Créer ou mettre à jour l'item local via og_id (évite les doublons)
        Item::updateOrCreate(
            ['og_id' => $ogId],
            [
                'sync_id' => $sync->id,
                'name' => $data['nom'] ?? $data['libelle'] ?? 'Sans nom',
                'code' => $data['code'] ?? null,
                'description' => null,
                'price' => (float)($data['prix'] ?? 0),
                'sale_price' => null,
                'stock' => (int)($data['quantite'] ?? 0),
                'category_id' => $categoryId,
                'brand_id' => null,
                'original_image' => $originalImage,
                'image' => $finalImage,
                'images' => !empty($localImages) ? $localImages : (!empty($gallery) ? $gallery : null),
                'status' => 'active',
            ]
        );
    }

    /**
     * Applique une catégorie synchronisée vers la table locale Category
     */
    private function applyCategoryToLocal(Synchronization $sync): void
    {
        $data = $sync->data;
        $imageService = new \App\Services\ImageDownloadService();

        // L'ID original de HomeIP
        $ogId = $sync->type_id;

        // Télécharger le logo (retourne null si placeholder)
        $originalLogo = $data['ImageURL'] ?? $data['LOGO'] ?? $data['IconURL'] ?? null;
        $localLogo = null;
        if ($originalLogo) {
            $localLogo = $imageService->downloadImage($originalLogo, 'categories');
        }
        
        // Si pas de logo local valide, utiliser no-image.png
        $finalLogo = $localLogo ?? 'no-image.png';

        // Créer ou mettre à jour la catégorie via og_id (évite les doublons)
        Category::updateOrCreate(
            ['og_id' => $ogId],
            [
                'sync_id' => $sync->id,
                'name' => $data['nom'] ?? 'Sans nom',
                'original_logo' => $originalLogo,
                'logo' => $finalLogo,
                'parent_id' => null,
            ]
        );
    }

    /**
     * POST /api/admin/synchronizations/mark-synced
     * Marque les entrées comme synchronisées sans les appliquer
     */
    public function markAsSynced(Request $request): JsonResponse
    {
        try {
            $ids = $request->get('ids', []);
            $updated = Synchronization::whereIn('id', $ids)->update(['status' => 'synced']);

            return response()->json([
                'success' => true,
                'message' => "$updated élément(s) marqué(s) comme synchronisé(s)"
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/synchronizations/migrate/{id}
     * Migre une seule entrée vers la table locale
     */
    public function migrateSingleEntry(int $id): JsonResponse
    {
        try {
            $sync = Synchronization::find($id);
            if (!$sync) {
                return response()->json(['success' => false, 'message' => 'Entrée non trouvée'], 404);
            }

            if ($sync->type === 'item') {
                $this->applyItemToLocal($sync);
            } elseif ($sync->type === 'category') {
                $this->applyCategoryToLocal($sync);
            }

            $sync->update([
                'status' => 'synced',
                'last_updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Migré avec succès',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur migration single', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $sync?->update(['status' => 'error']);
            
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/admin/synchronizations/count-pending
     * Compte les entrées en attente de migration par type
     */
    public function countPendingMigrations(Request $request): JsonResponse
    {
        try {
            // Compter les catégories en attente
            $categoryIds = Synchronization::where('type', 'category')
                ->whereIn('status', ['unsync', 'changed'])
                ->pluck('id')
                ->toArray();
            
            // Compter les items en attente
            $itemIds = Synchronization::where('type', 'item')
                ->whereIn('status', ['unsync', 'changed'])
                ->pluck('id')
                ->toArray();

            return response()->json([
                'success' => true,
                'categories' => [
                    'count' => count($categoryIds),
                    'ids' => $categoryIds
                ],
                'items' => [
                    'count' => count($itemIds),
                    'ids' => $itemIds
                ],
                'total' => count($categoryIds) + count($itemIds)
            ]);
        } catch (\Exception $e) {
            Log::error('countPendingMigrations error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/synchronizations/{id}
     * Supprimer une entrée de synchronisation
     */
    public function deleteSyncEntry(int $id): JsonResponse
    {
        try {
            $sync = Synchronization::find($id);
            if (!$sync) {
                return response()->json(['success' => false, 'message' => 'Entrée non trouvée'], 404);
            }

            $sync->delete();

            return response()->json(['success' => true, 'message' => 'Entrée supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/synchronizations
     * Supprimer plusieurs entrées de synchronisation
     */
    public function deleteSyncEntries(Request $request): JsonResponse
    {
        try {
            $ids = $request->get('ids', []);
            $deleted = Synchronization::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "$deleted entrée(s) supprimée(s)"
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Admin Users Management
    // ============================================

    /**
     * GET /api/admin/users
     * Liste des administrateurs
     */
    public function getAdmins(Request $request): JsonResponse
    {
        try {
            Log::info('API Admin: Liste des administrateurs', ['admin_id' => $request->user()?->id]);
            
            $admins = User::where('account_type', 'admin')
                ->latest()
                ->get()
                ->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'ccphone' => $u->ccphone ?? '',
                    'phone' => $u->phone ?? '',
                    'is_active' => !empty($u->password),
                    'status' => $u->status ?? 'active',
                    'created_at' => $u->created_at->format('Y-m-d'),
                ]);
            
            return response()->json(['success' => true, 'data' => $admins]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur liste admins', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/users
     * Créer un nouvel administrateur (invitation)
     */
    public function createAdmin(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'ccphone' => 'required|string',
                'phone' => 'required|string',
            ]);
            
            Log::info('API Admin: Création admin', ['email' => $validated['email']]);
            
            // Créer l'utilisateur sans mot de passe
            $activationToken = Str::random(64);
            
            $admin = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => '', // Vide, sera défini lors de l'activation
                'ccphone' => $validated['ccphone'],
                'phone' => $validated['phone'],
                'account_type' => 'admin',
                'reference' => 'ADMIN-' . strtoupper(Str::random(6)),
                'activation_token' => $activationToken,
                'activation_token_expires_at' => now()->addHours(72),
            ]);
            
            // Envoyer l'email d'invitation
            \Mail::to($admin->email)->send(new \App\Mail\AdminInvitationMail($admin, $activationToken));
            
            Log::info('API Admin: Admin créé et invitation envoyée', ['admin_id' => $admin->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Invitation envoyée avec succès.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()['email'][0] ?? 'Erreur de validation',
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur création admin', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/admin/users/{id}
     * Supprimer un administrateur
     */
    public function deleteAdmin(Request $request, int $id): JsonResponse
    {
        try {
            $admin = User::where('account_type', 'admin')->find($id);
            
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Administrateur non trouvé'], 404);
            }
            
            // Ne pas permettre de supprimer son propre compte
            if ($request->user()?->id === $id) {
                return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
            }
            
            Log::info('API Admin: Suppression admin', ['admin_id' => $id]);
            
            $admin->delete();
            
            return response()->json(['success' => true, 'message' => 'Administrateur supprimé']);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur suppression admin', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/users/{id}/resend-invitation
     * Renvoyer l'invitation à un admin
     */
    public function resendAdminInvitation(Request $request, int $id): JsonResponse
    {
        try {
            $admin = User::where('account_type', 'admin')
                ->where('password', '')
                ->find($id);
            
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Administrateur non trouvé ou déjà activé'], 404);
            }
            
            // Générer un nouveau token
            $activationToken = Str::random(64);
            $admin->update([
                'activation_token' => $activationToken,
                'activation_token_expires_at' => now()->addHours(72),
            ]);
            
            // Envoyer l'email
            \Mail::to($admin->email)->send(new \App\Mail\AdminInvitationMail($admin, $activationToken));
            
            Log::info('API Admin: Invitation renvoyée', ['admin_id' => $id]);
            
            return response()->json(['success' => true, 'message' => 'Invitation renvoyée avec succès']);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur renvoi invitation', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/admin/users/{id}/toggle-status
     * Activer/Désactiver un administrateur
     */
    public function toggleAdminStatus(Request $request, int $id): JsonResponse
    {
        try {
            $admin = User::where('account_type', 'admin')->find($id);
            
            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Administrateur non trouvé'], 404);
            }
            
            // Ne pas permettre de désactiver son propre compte
            if ($request->user()?->id === $id) {
                return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas désactiver votre propre compte'], 403);
            }
            
            $currentStatus = $admin->status ?? 'active';
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            
            $admin->update(['status' => $newStatus]);
            
            Log::info('API Admin: Toggle status admin', [
                'admin_id' => $id,
                'old_status' => $currentStatus,
                'new_status' => $newStatus,
                'by_admin_id' => $request->user()?->id,
            ]);
            
            // Envoyer un email si désactivé
            if ($newStatus === 'inactive') {
                try {
                    \Mail::to($admin->email)->send(new \App\Mail\AccountDeactivatedMail($admin));
                    Log::info('API Admin: Email de désactivation envoyé', ['admin_id' => $id]);
                } catch (\Exception $mailError) {
                    Log::warning('API Admin: Erreur envoi mail désactivation', ['error' => $mailError->getMessage()]);
                }
                
                // Révoquer tous les tokens de l'utilisateur
                $admin->tokens()->delete();
            }
            
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus === 'active' ? 'Compte réactivé avec succès' : 'Compte désactivé avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur toggle status', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/admin/activate (public route)
     * Activer un compte admin (définir mot de passe)
     */
    public function activateAdmin(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|min:8|confirmed',
            ]);
            
            $admin = User::where('email', $validated['email'])
                ->where('account_type', 'admin')
                ->where('activation_token', $validated['token'])
                ->where('activation_token_expires_at', '>', now())
                ->first();
            
            if (!$admin) {
                Log::warning('API Admin: Token activation invalide', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Lien d\'activation invalide ou expiré.',
                ], 400);
            }
            
            $admin->update([
                'password' => \Hash::make($validated['password']),
                'activation_token' => null,
                'activation_token_expires_at' => null,
                'email_verified_at' => now(),
            ]);
            
            // Créer un token de connexion
            $token = $admin->createToken('auth-token')->plainTextToken;
            
            Log::info('API Admin: Compte admin activé', ['admin_id' => $admin->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Compte activé avec succès !',
                'token' => $token,
                'user' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'account_type' => $admin->account_type,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Admin: Erreur activation admin', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
