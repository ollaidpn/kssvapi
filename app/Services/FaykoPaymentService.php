<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaykoPaymentService
{
    private string $publicKey;
    private string $secretKey;
    private string $baseUrl = 'https://fayko.sn/api/v1';

    public function __construct()
    {
        $this->publicKey = config('services.fayko.public_key');
        $this->secretKey = config('services.fayko.secret_key');
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
                'payment_method' => $payload['payment_method'] ?? null,
                'order_reference' => $payload['extra_data']['order_reference'] ?? null,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl . '/checkouts/make', [
                'public_key' => $this->publicKey,
                'payment_method' => $payload['payment_method'],
                'amount' => $payload['amount'],
                'currency' => $payload['currency'] ?? 'XOF',
                'extra_data' => json_encode($payload['extra_data'] ?? []),
                'webhook_url' => $payload['webhook_url'] ?? config('app.url') . '/api/webhook/fayko',
                'success_url' => $payload['success_url'] ?? null,
                'failure_url' => $payload['failure_url'] ?? null,
            ]);

            $data = $response->json();

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
                'message' => $data['message'] ?? 'Erreur lors de l\'initialisation du paiement',
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
}
