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
use Illuminate\Support\Facades\Log;
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
            
            $sections = $query->orderBy('type')->orderBy('id')->get();
            
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
                'address', 'town', 'country', 'maintenance'
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
}
