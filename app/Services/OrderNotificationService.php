<?php

namespace App\Services;

use App\Mail\NewOrderAlertMail;
use App\Models\AppInfo;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    /**
     * Envoie une alerte mail aux administrateurs pour une nouvelle commande.
     * L'échec d'un envoi ne bloque jamais le flux principal.
     */
    public function sendAdminNewOrderAlert(User $client, Order $order, int $itemsCount, string $paymentMethod): bool
    {
        try {
            $appInfo = AppInfo::first();

            if (!$appInfo) {
                Log::warning('OrderNotificationService: AppInfo introuvable pour alerte admin', [
                    'order_id' => $order->id,
                    'order_reference' => $order->reference,
                ]);
                return false;
            }

            $emails = collect([$appInfo->email1, $appInfo->email2])
                ->filter(fn($email) => is_string($email) && trim($email) !== '')
                ->unique()
                ->values();

            if ($emails->isEmpty()) {
                Log::warning('OrderNotificationService: Aucun email admin configuré', [
                    'order_id' => $order->id,
                    'order_reference' => $order->reference,
                ]);
                return false;
            }

            $sentCount = 0;

            foreach ($emails as $email) {
                try {
                    Mail::to($email)->send(new NewOrderAlertMail($client, $order, $itemsCount, $paymentMethod));
                    $sentCount++;

                    Log::info('OrderNotificationService: Alerte nouvelle commande envoyée', [
                        'order_id' => $order->id,
                        'order_reference' => $order->reference,
                        'email' => $email,
                    ]);
                } catch (\Throwable $emailError) {
                    Log::error('OrderNotificationService: Erreur envoi alerte admin', [
                        'order_id' => $order->id,
                        'order_reference' => $order->reference,
                        'email' => $email,
                        'error' => $emailError->getMessage(),
                    ]);
                }
            }

            return $sentCount > 0;
        } catch (\Throwable $e) {
            Log::error('OrderNotificationService: Erreur générale alerte admin', [
                'order_id' => $order->id,
                'order_reference' => $order->reference,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
