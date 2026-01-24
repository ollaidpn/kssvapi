<?php

namespace App\Services;

use App\Models\AppInfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationsService
{
    private string $appKey;
    private string $sender;
    private string $endpoint;

    public function __construct()
    {
        $this->appKey = config('services.intech_sms.app_key');
        $this->sender = config('services.intech_sms.sender');
        $this->endpoint = config('services.intech_sms.endpoint');
    }

    /**
     * Normaliser le message SMS (supprimer accents pour optimiser le coût)
     */
    public function normalizeSms(string $message): string
    {
        $accents = [
            'àáâãäå' => 'a',
            'èéêë' => 'e',
            'ìíîï' => 'i',
            'òóôõö' => 'o',
            'ùúûü' => 'u',
            'ç' => 'c',
            'ñ' => 'n',
            'ÀÁÂÃÄÅ' => 'A',
            'ÈÉÊË' => 'E',
            'ÌÍÎÏ' => 'I',
            'ÒÓÔÕÖ' => 'O',
            'ÙÚÛÜ' => 'U',
            'Ç' => 'C',
            'Ñ' => 'N',
        ];

        foreach ($accents as $chars => $replace) {
            $message = preg_replace('/[' . $chars . ']/', $replace, $message);
        }
        
        return $message;
    }

    /**
     * Envoyer un SMS à un ou plusieurs destinataires
     *
     * @param array $recipients Tableau de numéros (format international +221XXXXXXXXX)
     * @param string $message Contenu du message
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendSms(array $recipients, string $message): array
    {
        try {
            $normalizedMessage = $this->normalizeSms($message);
            $results = [];
            
            foreach ($recipients as $phone) {
                // Skip empty phone numbers
                if (empty($phone) || $phone === '+') {
                    continue;
                }
                
                $response = Http::timeout(10)->post($this->endpoint, [
                    'app_key' => $this->appKey,
                    'sender' => $this->sender,
                    'msisdn' => $phone,
                    'message' => $normalizedMessage,
                ]);
                
                $responseData = $response->json();
                
                Log::info('SMS envoyé via Intech', [
                    'phone' => $phone,
                    'message_length' => strlen($normalizedMessage),
                    'response_code' => $responseData['code'] ?? null,
                    'response_error' => $responseData['error'] ?? null,
                    'response_msg' => $responseData['msg'] ?? null,
                ]);
                
                $results[] = [
                    'phone' => $phone,
                    'success' => !($responseData['error'] ?? true),
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Message envoyé.',
                'results' => $results,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur envoi SMS Intech', [
                'error' => $e->getMessage(),
                'recipients' => $recipients,
            ]);
            
            return [
                'success' => false,
                'message' => 'Échec de l\'envoi du message.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer un SMS à l'admin (appInfo->ccphone2 + phone2)
     *
     * @param string $message Contenu du message
     * @return void
     */
    public function sendSmsToAdmin(string $message): void
    {
        try {
            $appInfo = AppInfo::first();
            
            if ($appInfo && $appInfo->phone2) {
                $phone = '+' . ($appInfo->ccphone2 ?? '221') . $appInfo->phone2;
                $this->sendSms([$phone], $message);
                
                Log::info('SMS admin envoyé', ['phone' => $phone]);
            } else {
                Log::warning('SMS admin non envoyé: pas de numéro phone2 configuré');
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi SMS admin', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Envoyer un SMS de bienvenue à un nouveau client
     *
     * @param string $phone Numéro complet avec indicatif (+221XXXXXXXXX)
     * @param string $name Nom du client
     * @param string $reference Référence client
     * @return array
     */
    public function sendWelcomeSms(string $phone, string $name, string $reference): array
    {
        $message = "Bienvenue chez KSSV ! Votre compte client est actif. Reference: {$reference}. Merci de votre confiance.";
        return $this->sendSms([$phone], $message);
    }

    /**
     * Envoyer un SMS de confirmation de commande au client
     *
     * @param string $phone Numéro complet avec indicatif
     * @param string $reference Référence commande
     * @param float $total Montant total
     * @param bool $isPaid Si la commande est payée
     * @return array
     */
    public function sendOrderConfirmationSms(string $phone, string $reference, float $total, bool $isPaid = false): array
    {
        $formattedTotal = number_format($total, 0, '', ' ');
        
        if ($isPaid) {
            $message = "KSSV: Merci pour votre commande {$reference}. Paiement recu ({$formattedTotal} FCFA). Livraison en cours de preparation.";
        } else {
            $message = "KSSV: Commande {$reference} confirmee ({$formattedTotal} FCFA). Paiement a la livraison. Nous vous contacterons bientot.";
        }
        
        return $this->sendSms([$phone], $message);
    }

    /**
     * Envoyer une alerte SMS à l'admin pour une nouvelle commande
     *
     * @param string $reference Référence commande
     * @param string $clientName Nom du client
     * @param float $total Montant total
     * @param string $paymentMethod Méthode de paiement
     * @return void
     */
    public function sendNewOrderAlertSms(string $reference, string $clientName, float $total, string $paymentMethod): void
    {
        $formattedTotal = number_format($total, 0, '', ' ');
        $paymentLabel = match($paymentMethod) {
            'cash_on_delivery' => 'Livraison',
            'wave_senegal' => 'Wave',
            'orange_money_senegal' => 'OM',
            default => $paymentMethod,
        };
        
        $message = "KSSV: Nouvelle commande {$reference} de {$clientName}. Montant: {$formattedTotal} FCFA. Mode: {$paymentLabel}.";
        $this->sendSmsToAdmin($message);
    }
}
