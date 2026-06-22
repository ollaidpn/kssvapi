<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * POST /api/webhook/fayko
     * Traite les webhooks de paiement Fayko
     */
    public function fayko(Request $request): JsonResponse
    {
        try {
            Log::info('Webhook Fayko: Réception', [
                'payload' => $request->all()
            ]);

            // Récupérer les données du webhook
            $transactionId = $request->input('transaction_id');
            $status = $request->input('status');
            $amount = $request->input('amount');
            $extraDataRaw = $request->input('extra_data');
            
            // Décoder extra_data (peut être string JSON ou array)
            $extraData = is_string($extraDataRaw) 
                ? json_decode($extraDataRaw, true) 
                : $extraDataRaw;
            
            $orderReference = $extraData['order_reference'] ?? null;

            if (!$orderReference) {
                Log::warning('Webhook Fayko: order_reference manquant', [
                    'transaction_id' => $transactionId,
                    'extra_data' => $extraDataRaw
                ]);
                return response()->json(['message' => 'order_reference missing'], 200);
            }

            // Trouver la commande
            $order = Order::where('reference', $orderReference)->first();

            if (!$order) {
                Log::warning('Webhook Fayko: Commande non trouvée', [
                    'order_reference' => $orderReference,
                    'transaction_id' => $transactionId
                ]);
                return response()->json(['message' => 'Order not found'], 200);
            }

            // Éviter de retraiter une commande déjà payée
            if ($order->status === 'paid') {
                Log::info('Webhook Fayko: Commande déjà payée, ignoré', [
                    'order_reference' => $orderReference
                ]);
                return response()->json(['message' => 'Order already paid'], 200);
            }

            // Mettre à jour selon le statut
            if ($status === 'success') {
                $order->status = 'paid';
                $order->payment_status = 'paid';
                $order->save();

                // Mettre à jour le paiement existant (créé lors de la commande)
                $payment = Payment::where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->first();

                if ($payment) {
                    $payment->update([
                        'transaction_id' => $transactionId,
                        'status' => 'success',
                        'date' => now(),
                    ]);

                    Log::info('Webhook Fayko: Paiement existant mis à jour', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transactionId
                    ]);
                } else {
                    // Fallback: créer le paiement si pas trouvé
                    Payment::create([
                        'order_id' => $order->id,
                        'amount' => $amount ?? $order->total,
                        'paid_by' => $order->payment_method,
                        'date' => now(),
                        'reference' => $order->reference,
                        'transaction_id' => $transactionId,
                        'status' => 'success',
                    ]);

                    Log::info('Webhook Fayko: Nouveau paiement créé (fallback)', [
                        'order_id' => $order->id,
                        'transaction_id' => $transactionId
                    ]);
                }

                Log::info('Webhook Fayko: Paiement réussi', [
                    'order_id' => $order->id,
                    'order_reference' => $orderReference,
                    'transaction_id' => $transactionId,
                    'amount' => $amount
                ]);
            } else {
                // Statut d'échec ou autre
                $order->status = 'failed';
                $order->payment_status = 'failed';
                $order->save();

                // Marquer le paiement comme échoué
                Payment::where('order_id', $order->id)
                    ->where('status', 'pending')
                    ->update([
                        'transaction_id' => $transactionId,
                        'status' => 'failed',
                    ]);

                Log::info('Webhook Fayko: Paiement échoué', [
                    'order_id' => $order->id,
                    'order_reference' => $orderReference,
                    'status' => $status
                ]);
            }

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook Fayko: Erreur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            // Retourner 200 pour éviter les retries de Fayko
            return response()->json(['message' => 'Error processing webhook'], 200);
        }
    }
}
