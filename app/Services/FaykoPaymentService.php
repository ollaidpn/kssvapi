<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaykoPaymentService
{
    private string $publicKey;
    private string $secretKey;
    private string $webhookKey;
    private string $baseUrl = 'https://fayko.sn/api/v1';

    public function __construct()
    {
        $this->publicKey = config('services.fayko.public_key');
        $this->secretKey = config('services.fayko.secret_key');
        $this->webhookKey = config('services.fayko.webhook_key');
    }

    /**
     * Initier un paiement via Fayko
     *
     * @param array $payload
     * @return array
     */
    public function makePayment(array $payload): array
    {
        try {
            Log::info('FaykoPaymentService: Initialisation paiement', [
                'amount' => $payload['amount'] ?? null,
                'qty' => $payload['qty'] ?? 1,
                'payment_method' => $payload['payment_method'] ?? null,
                'order_reference' => $payload['extra_data']['order_reference'] ?? null,
            ]);

            // Mapper le moyen de paiement au format Fayko
            $paidBy = $this->mapPaymentMethod($payload['payment_method'] ?? 'wave');

            $requestBody = [
                'client_name' => $payload['client_name'] ?? 'Client',
                'name'        => $payload['name'] ?? 'Commande',
                'description' => $payload['description'] ?? 'Achat sur KSSV',
                'amount'      => (int) $payload['amount'],
                'qty'         => (int) ($payload['qty'] ?? 1),
                'paid_by'     => $paidBy,
                'ccphone'     => $payload['ccphone'] ?? '+221',
                'phone'       => $payload['phone'] ?? '',
                'error_url'   => $payload['error_url'] ?? config('app.url'),
                'success_url' => $payload['success_url'] ?? config('app.url'),
                'extra_data'  => json_encode($payload['extra_data'] ?? []),
            ];

            Log::info('FaykoPaymentService: Request body', $requestBody);

            $response = Http::withHeaders([
                'public-key'   => $this->publicKey,
                'secret-key'   => $this->secretKey,
                'webhook-key'  => $this->webhookKey,
                'content-type' => 'application/json',
            ])->post($this->baseUrl . '/checkouts/make', $requestBody);

            $data = $response->json();

            Log::info('FaykoPaymentService: Response', [
                'status' => $response->status(),
                'response' => $data,
            ]);

            if ($response->successful() && isset($data['payment_link'])) {
                Log::info('FaykoPaymentService: Paiement initié avec succès', [
                    'payment_link' => $data['payment_link'] ?? null,
                    'expires' => $data['when_expires'] ?? null,
                ]);

                return [
                    'success' => true,
                    'payment_link' => $data['payment_link'],
                    'payment_qrcode_base64' => $data['payment_qrcode_base64'] ?? null,
                    'when_expires' => $data['when_expires'] ?? null,
                ];
            }

            Log::error('FaykoPaymentService: Échec initialisation paiement', [
                'status' => $response->status(),
                'response' => $data,
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? $data['error'] ?? 'Erreur lors de l\'initialisation du paiement',
            ];
        } catch (\Exception $e) {
            Log::error('FaykoPaymentService: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de connexion au service de paiement',
            ];
        }
    }

    /**
     * Convertir le nom du moyen de paiement en format Fayko
     *
     * @param string $method
     * @return string
     */
    private function mapPaymentMethod(string $method): string
    {
        return match($method) {
            'wave_senegal' => 'wave',
            'orange_money_senegal' => 'orange_money',
            default => $method
        };
    }
}
