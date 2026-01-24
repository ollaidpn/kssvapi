<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\AppInfo;
use App\Models\User;
use App\Helpers\Shortcut;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\FaykoPaymentService;
use App\Mail\OrderConfirmationMail;
use App\Mail\NewOrderAlertMail;

class AccountController extends Controller
{
    /**
     * GET /api/account/dashboard
     * Retourne les statistiques du client connecté
     * Ne compte que les commandes COD ou totalement payées
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Account: Acces dashboard client', ['user_id' => $user->id]);
            
            // Nombre de commandes valides (COD ou payées)
            $ordersCount = $user->orders()
                ->where(function($query) {
                    $query->where('payment_method', 'cash_on_delivery')
                          ->orWhere('payment_status', 'paid');
                })
                ->count();
            
            // Nombre d'articles dans le panier (status = pending, type = cart)
            $cartCount = $user->carts()
                ->where('status', 'pending')
                ->where('type', 'cart')
                ->sum('qty');
            
            // Total dépensé = somme des commandes confirmées (COD ou payées)
            $totalSpent = $user->orders()
                ->where(function($query) {
                    $query->where('payment_method', 'cash_on_delivery')
                          ->orWhere('payment_status', 'paid');
                })
                ->sum('total');
            
            // Commandes récentes (5 dernières, toutes confondues pour l'affichage)
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

    /**
     * POST /api/orders
     * Créer une commande à partir du panier
     * Supporte: cash_on_delivery, wave_senegal, orange_money_senegal
     */
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'payment_method' => 'required|string|in:cash_on_delivery,wave_senegal,orange_money_senegal',
                'promo_code_id' => 'nullable|integer|exists:promo_codes,id',
            ]);
            
            $user = $request->user();
            $paymentMethod = $validated['payment_method'];
            $promoCodeId = $validated['promo_code_id'] ?? null;
            
            Log::info('API Account: Création commande', [
                'user_id' => $user->id,
                'payment_method' => $paymentMethod,
                'promo_code_id' => $promoCodeId
            ]);
            
            // Récupérer les articles du panier
            $cartItems = Cart::where('user_id', $user->id)
                ->where('type', 'cart')
                ->where('status', 'pending')
                ->get();
            
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre panier est vide'
                ], 400);
            }
            
            // Calculer le sous-total
            $subtotal = $cartItems->sum(fn($item) => $item->price * $item->qty);
            
            // Appliquer la réduction du code promo si présent
            $discount = 0;
            if ($promoCodeId) {
                $promoCode = \App\Models\PromoCode::find($promoCodeId);
                if ($promoCode && $promoCode->status === 'active') {
                    if ($promoCode->discount_by === 'percent') {
                        $discount = round($subtotal * ($promoCode->discount_value / 100));
                    } else {
                        $discount = $promoCode->discount_value;
                    }
                }
            }
            
            // Total final
            $total = max(0, $subtotal - $discount);
            
            // Générer référence unique
            $reference = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Déterminer le statut initial selon le mode de paiement
            $initialStatus = $paymentMethod === 'cash_on_delivery' ? 'pending' : 'processing';
            
            // Créer la commande
            $order = Order::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'total' => $total,
                'discount' => $discount,
                'status' => $initialStatus,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'payment_method' => $paymentMethod,
                'promo_code_id' => $promoCodeId,
            ]);
            
            // Lier les articles du panier à la commande et changer le type
            Cart::where('user_id', $user->id)
                ->where('type', 'cart')
                ->where('status', 'pending')
                ->update([
                    'order_id' => $order->id,
                    'type' => 'order',
                    'status' => 'ordered',
                    'total' => DB::raw('price * qty'),
                ]);
            
            Log::info('API Account: Commande créée', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'reference' => $reference,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'items_count' => $cartItems->count()
            ]);

            // Si paiement mobile (Wave ou Orange Money), appeler Fayko
            if (in_array($paymentMethod, ['wave_senegal', 'orange_money_senegal'])) {
                // Créer le paiement AVANT d'appeler Fayko (pour avoir le payment_id dans success_url)
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'amount' => $order->total,
                    'paid_by' => $paymentMethod,
                    'date' => now(),
                    'reference' => $order->reference,
                    'status' => 'pending',
                    'payment_link' => null,
                    'expires_at' => null,
                ]);
                
                Log::info('API Account: Paiement pending créé AVANT Fayko', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                ]);
                
                $faykoService = new FaykoPaymentService();
                
                // Construire la description avec la liste des articles
                $description = $this->buildOrderDescription($cartItems);
                
                // Construire le success_url avec le payment_id
                $frontendUrl = Shortcut::getFrontendUrl($request);
                $successUrl = $frontendUrl . '/paiement-reussi?payment_id=' . $payment->id;
                $errorUrl = $frontendUrl . '/checkout';
                
                $paymentResult = $faykoService->makePayment([
                    'payment_method' => $paymentMethod,
                    'amount' => (int) $total,  // Entier, pas de décimales
                    'qty' => 1,  // Toujours 1 pour une commande globale
                    'client_name' => $user->name ?? 'Client',
                    'name' => 'Commande KSSV',
                    'description' => $description,
                    'ccphone' => '+221',
                    'phone' => $user->phone ?? '',
                    'extra_data' => [
                        'origin' => 'kssv',
                        'order_reference' => $reference,
                        'user_id' => $user->id,
                        'payment_id' => $payment->id,
                    ],
                    'error_url' => $errorUrl,
                    'success_url' => $successUrl,
                ]);
                
                if (!$paymentResult['success']) {
                    // Annuler le paiement et la commande si Fayko échoue
                    $payment->status = 'failed';
                    $payment->save();
                    
                    $order->status = 'failed';
                    $order->save();
                    
                    // Remettre les articles dans le panier
                    Cart::where('order_id', $order->id)->update([
                        'order_id' => null,
                        'type' => 'cart',
                        'status' => 'pending',
                        'total' => null,
                    ]);
                    
                    Log::error('API Account: Échec initialisation paiement Fayko', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'error' => $paymentResult['message'] ?? 'Unknown error'
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message'] ?? 'Erreur lors de l\'initialisation du paiement'
                    ], 500);
                }
                
                // Mettre à jour le paiement avec les infos Fayko
                $payment->update([
                    'payment_link' => $paymentResult['payment_link'] ?? null,
                    'expires_at' => isset($paymentResult['when_expires']) 
                        ? \Carbon\Carbon::parse($paymentResult['when_expires']) 
                        : null,
                ]);

                Log::info('API Account: Paiement pending créé', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'payment_link' => $paymentResult['payment_link'] ?? null
                ]);
                
                // Récupérer les articles pour l'email
                $orderItems = Cart::where('order_id', $order->id)->get();
                
                // Envoyer email de confirmation au client (paiement en ligne = payé)
                try {
                    Mail::to($user->email)->send(new OrderConfirmationMail($user, $order, $orderItems, true));
                    Log::info('API Account: Email confirmation (payé) envoyé au client', ['order_id' => $order->id]);
                } catch (\Exception $emailError) {
                    Log::error('API Account: Erreur envoi email confirmation', ['error' => $emailError->getMessage()]);
                }
                
                // Envoyer alerte à l'admin
                try {
                    $appInfo = AppInfo::first();
                    if ($appInfo && $appInfo->email1) {
                        Mail::to($appInfo->email1)->send(new NewOrderAlertMail($user, $order, $orderItems->count(), $paymentMethod));
                        Log::info('API Account: Alerte nouvelle commande envoyée à admin', ['email' => $appInfo->email1]);
                    }
                } catch (\Exception $emailError) {
                    Log::error('API Account: Erreur envoi alerte admin', ['error' => $emailError->getMessage()]);
                }
                
                // SMS confirmation au client (commande payée)
                try {
                    $smsService = new \App\Services\NotificationsService();
                    $clientPhone = '+' . ($user->ccphone ?? '221') . $user->phone;
                    $smsService->sendOrderConfirmationSms($clientPhone, $order->reference, $order->total, true);
                    Log::info('API Account: SMS confirmation (payé) envoyé au client', ['phone' => $clientPhone]);
                } catch (\Exception $smsError) {
                    Log::error('API Account: Erreur envoi SMS confirmation client', ['error' => $smsError->getMessage()]);
                }
                
                // SMS alerte à l'admin
                try {
                    $smsService = new \App\Services\NotificationsService();
                    $smsService->sendNewOrderAlertSms($order->reference, $user->name, $order->total, $paymentMethod);
                    Log::info('API Account: SMS alerte nouvelle commande envoyé à admin');
                } catch (\Exception $smsError) {
                    Log::error('API Account: Erreur envoi SMS admin', ['error' => $smsError->getMessage()]);
                }
                
                // Retourner les infos de paiement Fayko
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement initialisé, en attente de confirmation',
                    'data' => [
                        'order_id' => $order->id,
                        'reference' => $order->reference,
                        'payment_id' => $payment->id,
                        'total' => (float) $order->total,
                        'status' => 'processing',
                        'payment_link' => $paymentResult['payment_link'] ?? null,
                        'payment_qrcode_base64' => $paymentResult['payment_qrcode_base64'] ?? null,
                        'when_expires' => $paymentResult['when_expires'] ?? null,
                    ]
                ], 201);
            }

            // Récupérer les articles pour l'email
            $orderItems = Cart::where('order_id', $order->id)->get();
            
            // Envoyer email de confirmation au client (COD = non payé)
            try {
                Mail::to($user->email)->send(new OrderConfirmationMail($user, $order, $orderItems, false));
                Log::info('API Account: Email confirmation envoyé au client', ['order_id' => $order->id, 'email' => $user->email]);
            } catch (\Exception $emailError) {
                Log::error('API Account: Erreur envoi email confirmation client', ['error' => $emailError->getMessage()]);
            }
            
            // Envoyer alerte à l'admin (appInfo->email1)
            try {
                $appInfo = AppInfo::first();
                if ($appInfo && $appInfo->email1) {
                    Mail::to($appInfo->email1)->send(new NewOrderAlertMail($user, $order, $orderItems->count(), $paymentMethod));
                    Log::info('API Account: Alerte nouvelle commande envoyée à admin', ['email' => $appInfo->email1]);
                }
            } catch (\Exception $emailError) {
                Log::error('API Account: Erreur envoi alerte admin', ['error' => $emailError->getMessage()]);
            }
            
            // SMS confirmation au client (commande COD = non payée)
            try {
                $smsService = new \App\Services\NotificationsService();
                $clientPhone = '+' . ($user->ccphone ?? '221') . $user->phone;
                $smsService->sendOrderConfirmationSms($clientPhone, $order->reference, $order->total, false);
                Log::info('API Account: SMS confirmation envoyé au client', ['phone' => $clientPhone]);
            } catch (\Exception $smsError) {
                Log::error('API Account: Erreur envoi SMS confirmation client', ['error' => $smsError->getMessage()]);
            }
            
            // SMS alerte à l'admin
            try {
                $smsService = new \App\Services\NotificationsService();
                $smsService->sendNewOrderAlertSms($order->reference, $user->name, $order->total, $paymentMethod);
                Log::info('API Account: SMS alerte nouvelle commande envoyé à admin');
            } catch (\Exception $smsError) {
                Log::error('API Account: Erreur envoi SMS admin', ['error' => $smsError->getMessage()]);
            }

            // Paiement à la livraison - réponse classique
            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                    'total' => (float) $order->total,
                    'status' => 'pending',
                ]
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur création commande', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande'
            ], 500);
        }
    }

    /**
     * POST /api/orders/check
     * Vérifier le statut d'une commande (pour le polling)
     */
    public function checkOrderStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_reference' => 'required|string',
            ]);
            
            $user = $request->user();
            
            $order = Order::where('reference', $validated['order_reference'])
                ->where('user_id', $user->id)
                ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable'
                ], 404);
            }
            
            Log::info('API Account: Check order status', [
                'order_reference' => $validated['order_reference'],
                'status' => $order->status
            ]);
            
            return response()->json([
                'success' => true,
                'status' => $order->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('API Account: Erreur check order status', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification'
            ], 500);
        }
    }

    /**
     * POST /api/orders/cancel
     * Annuler une commande en attente
     */
    public function cancelOrder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_reference' => 'required|string',
            ]);
            
            $user = $request->user();
            
            $order = Order::where('reference', $validated['order_reference'])
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'processing'])
                ->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande introuvable ou non annulable'
                ], 404);
            }
            
            // Annuler la commande
            $order->status = 'cancelled';
            $order->save();

            // Annuler les paiements en attente
            Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
            
            // Remettre les articles dans le panier
            Cart::where('order_id', $order->id)->update([
                'order_id' => null,
                'type' => 'cart',
                'status' => 'pending',
                'total' => null,
            ]);
            
            Log::info('API Account: Commande annulée', [
                'order_id' => $order->id,
                'order_reference' => $order->reference,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('API Account: Erreur annulation commande', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }
    
    /**
     * POST /api/promo-codes/validate
     * Valider un code promo
     */
    public function validatePromoCode(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(['code' => 'required|string']);
            
            $promo = \App\Models\PromoCode::where('code', $validated['code'])
                ->where('status', 'active')
                ->first();
            
            if (!$promo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code promo invalide'
                ]);
            }
            
            // Vérifier expiration
            if ($promo->filter_by === 'date' && $promo->expiration && $promo->expiration < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce code promo a expiré'
                ]);
            }
            
            // Vérifier limite d'utilisation
            if ($promo->limite !== null && $promo->limite > 0) {
                $usageCount = Order::where('promo_code_id', $promo->id)->count();
                if ($usageCount >= $promo->limite) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce code promo a atteint sa limite d\'utilisation'
                    ]);
                }
            }
            
            Log::info('API Account: Code promo validé', [
                'user_id' => $request->user()->id,
                'promo_code' => $promo->code
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $promo->id,
                    'code' => $promo->code,
                    'discount_by' => $promo->discount_by,
                    'discount_value' => (float) $promo->discount_value,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur validation code promo', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du code promo'
            ], 500);
        }
    }
    
    /**
     * Construire la description de la commande pour Fayko
     */
    private function buildOrderDescription($cartItems): string
    {
        $lines = [];
        foreach ($cartItems as $item) {
            $infos = $item->item_infos ?? [];
            $name = $infos['name'] ?? 'Article';
            $lines[] = "{$item->qty}x {$name}";
        }
        return implode(', ', $lines);
    }

    /**
     * POST /api/account/avatar
     * Upload/Update de la photo de profil
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'avatar' => 'required|image|max:2048', // Max 2MB
            ]);

            $user = $request->user();
            
            // Supprimer l'ancien avatar si existe
            if ($user->avatar) {
                $oldPath = public_path($user->avatar);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            // Sauvegarder le nouvel avatar
            $file = $request->file('avatar');
            $fileName = 'avatar-' . $user->id . '-' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/avatars'), $fileName);
            
            $avatarPath = 'uploads/avatars/' . $fileName;
            $user->avatar = $avatarPath;
            $user->save();
            
            // Mettre à jour le localStorage côté client
            Log::info('API Account: Avatar mis à jour', [
                'user_id' => $user->id,
                'avatar' => $avatarPath
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo de profil mise à jour',
                'avatar' => asset($avatarPath),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ccphone' => $user->ccphone,
                    'phone' => $user->phone,
                    'reference' => $user->reference,
                    'account_type' => $user->account_type,
                    'avatar' => asset($avatarPath),
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Format ou taille de fichier invalide (max 2MB)',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur upload avatar', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload de la photo'
            ], 500);
        }
    }

    /**
     * GET /api/order/{paymentId}
     * Route PUBLIQUE - Récupérer les détails d'une commande via payment_id
     * Utilisée par la page de succès de paiement
     */
    public function getOrderByPayment(int $paymentId): JsonResponse
    {
        try {
            Log::info('API Account: Accès public order par payment_id', ['payment_id' => $paymentId]);
            
            // Trouver le paiement
            $payment = Payment::with('order')->find($paymentId);
            
            if (!$payment || !$payment->order) {
                Log::warning('API Account: Paiement non trouvé', ['payment_id' => $paymentId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement non trouvé'
                ], 404);
            }
            
            $order = $payment->order;
            $user = User::find($order->user_id);
            $cartItems = Cart::where('order_id', $order->id)->get();
            
            // Vérifier le statut du paiement
            $isPaid = $payment->status === 'success' || $order->payment_status === 'paid';
            
            // Mapper les items
            $items = $cartItems->map(function($cart) {
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
            
            // Calculer le résumé
            $subtotal = $items->sum('total');
            $totalPaid = $isPaid ? (float) $payment->amount : 0;
            
            Log::info('API Account: Order récupéré via payment_id', [
                'payment_id' => $paymentId,
                'order_id' => $order->id,
                'is_paid' => $isPaid,
                'payment_status' => $payment->status
            ]);
            
            return response()->json([
                'success' => true,
                'is_paid' => $isPaid,
                'payment_status' => $payment->status,
                'data' => [
                    'payment' => [
                        'id' => $payment->id,
                        'reference' => $payment->reference,
                        'amount' => (float) $payment->amount,
                        'status' => $payment->status,
                        'paid_by' => $payment->paid_by,
                        'date' => $payment->date?->format('d/m/Y'),
                    ],
                    'order' => [
                        'id' => $order->id,
                        'reference' => $order->reference,
                        'total' => (float) $order->total,
                        'discount' => (float) ($order->discount ?? 0),
                        'status' => $order->status,
                        'payment_status' => $order->payment_status ?? 'pending',
                        'delivery_status' => $order->delivery_status ?? 'pending',
                        'payment_method' => $order->payment_method,
                        'address' => $order->address,
                        'city' => $order->city,
                        'created_at' => $order->created_at->format('d/m/Y H:i'),
                        'updated_at' => $order->updated_at->format('d/m/Y H:i'),
                    ],
                    'client' => [
                        'id' => $user?->id,
                        'name' => $user?->name ?? 'Client',
                        'email' => $user?->email ?? '',
                        'phone' => ($user?->ccphone ?? '') . ' ' . ($user?->phone ?? ''),
                    ],
                    'promo_code' => $order->promoCode ? [
                        'code' => $order->promoCode->code,
                        'value' => $order->promoCode->discount_value,
                    ] : null,
                    'items' => $items,
                    'payments' => [[
                        'id' => $payment->id,
                        'reference' => $payment->reference,
                        'amount' => (float) $payment->amount,
                        'paid_by' => $payment->paid_by,
                        'payment_type' => 'online',
                        'date' => $payment->date?->format('d/m/Y'),
                        'created_at' => $payment->created_at->format('d/m/Y H:i'),
                    ]],
                    'summary' => [
                        'subtotal' => $subtotal,
                        'discount' => (float) ($order->discount ?? 0),
                        'total' => (float) $order->total,
                        'total_paid' => $totalPaid,
                        'remaining' => (float) $order->total - $totalPaid,
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Account: Erreur getOrderByPayment', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la commande'
            ], 500);
        }
    }
}
