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
     * Formater et valider un numéro de téléphone selon le format Intech SMS
     * 
     * Format attendu par Intech: +221XXXXXXXXX (format international avec +)
     * 
     * Gère les cas suivants:
     * - ccphone=221, phone=771234567 → +221771234567
     * - ccphone=+221, phone=771234567 → +221771234567
     * - phone=+221771234567 → +221771234567
     * - phone=221771234567 → +221771234567
     * - phone=00221771234567 → +221771234567
     *
     * @param string|null $ccphone Code pays (ex: 221, +221)
     * @param string|null $phone Numéro (ex: 771234567 ou +221771234567)
     * @return string|null Numéro formaté (+221771234567) ou null si invalide
     */
    public function formatPhoneNumber(?string $ccphone, ?string $phone): ?string
    {
        // Vérifier que le numéro existe
        if (empty($phone)) {
            Log::warning('SMS: Numéro de téléphone vide', ['ccphone' => $ccphone, 'phone' => $phone]);
            return null;
        }
        
        // Supprimer tous les caractères non numériques (espaces, +, tirets, etc.)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si le numéro commence par 00221, supprimer le 00 (format international alternatif)
        if (str_starts_with($phone, '00221')) {
            $phone = substr($phone, 2); // Devient 221XXXXXXX
        }
        
        // Si le numéro contient déjà 221 au début (221771234567), c'est un numéro complet
        if (str_starts_with($phone, '221') && strlen($phone) >= 12) {
            return '+' . $phone;
        }
        
        // Nettoyer le code pays (supprimer +, espaces, etc.)
        $ccphone = preg_replace('/[^0-9]/', '', $ccphone ?? '221');
        
        // Si ccphone est vide après nettoyage, utiliser 221 par défaut (Sénégal)
        if (empty($ccphone)) {
            $ccphone = '221';
        }
        
        // Vérifier la longueur du numéro local
        // Sénégal = 9 chiffres (77XXXXXXX, 78XXXXXXX, 76XXXXXXX, 70XXXXXXX)
        // Accepter entre 7 et 10 chiffres pour flexibilité internationale
        if (strlen($phone) < 7 || strlen($phone) > 10) {
            Log::warning('SMS: Numéro de téléphone invalide (longueur)', [
                'phone' => $phone,
                'length' => strlen($phone),
                'expected' => '7-10 chiffres pour numéro local'
            ]);
            return null;
        }
        
        // Si le numéro local commence déjà par le code pays, ne pas doubler
        if (str_starts_with($phone, $ccphone)) {
            return '+' . $phone;
        }
        
        // Format final : +221771234567
        return '+' . $ccphone . $phone;
    }

    /**
     * Vérifier si un numéro est valide pour l'envoi SMS Intech
     * 
     * @param string|null $phone Numéro formaté (+221XXXXXXXXX)
     * @return bool
     */
    public function isValidPhoneNumber(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }
        
        // Doit commencer par +
        if (!str_starts_with($phone, '+')) {
            return false;
        }
        
        // Extraire les chiffres
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        // Numéro valide : entre 10 et 15 chiffres (standard international E.164)
        return strlen($digits) >= 10 && strlen($digits) <= 15;
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
            // Vérifier la configuration
            if (empty($this->appKey) || empty($this->endpoint)) {
                Log::error('SMS: Configuration Intech SMS manquante', [
                    'app_key_set' => !empty($this->appKey),
                    'sender' => $this->sender,
                    'endpoint' => $this->endpoint,
                ]);
                return [
                    'success' => false,
                    'message' => 'Configuration SMS manquante.',
                ];
            }
            
            $normalizedMessage = $this->normalizeSms($message);
            $results = [];
            
            foreach ($recipients as $phone) {
                // Valider le numéro avec la méthode dédiée (format Intech: +XXXXXXXXXXXX)
                if (!$this->isValidPhoneNumber($phone)) {
                    Log::warning('SMS: Numéro ignoré (format invalide)', [
                        'phone' => $phone,
                        'reason' => 'Ne respecte pas le format international +XXXXXXXXXXXX (Intech SMS)'
                    ]);
                    continue;
                }
                
                Log::info('SMS: Envoi en cours...', [
                    'phone' => $phone,
                    'sender' => $this->sender,
                    'message_length' => strlen($normalizedMessage),
                    'endpoint' => $this->endpoint,
                ]);
                
                $response = Http::timeout(10)->post($this->endpoint, [
                    'app_key' => $this->appKey,
                    'sender' => $this->sender,
                    'msisdn' => $phone,
                    'message' => $normalizedMessage,
                ]);
                
                $responseData = $response->json();
                
                $isSuccess = !($responseData['error'] ?? true);
                
                Log::info('SMS: Réponse Intech', [
                    'phone' => $phone,
                    'success' => $isSuccess,
                    'response_code' => $responseData['code'] ?? null,
                    'response_error' => $responseData['error'] ?? null,
                    'response_msg' => $responseData['msg'] ?? null,
                ]);
                
                $results[] = [
                    'phone' => $phone,
                    'success' => $isSuccess,
                    'response' => $responseData,
                ];
            }
            
            $hasSuccess = collect($results)->contains('success', true);
            
            return [
                'success' => $hasSuccess,
                'message' => $hasSuccess ? 'Message envoyé.' : 'Aucun message envoyé.',
                'results' => $results,
            ];
        } catch (\Exception $e) {
            Log::error('SMS: Exception lors de l\'envoi', [
                'error' => $e->getMessage(),
                'recipients' => $recipients,
                'trace' => $e->getTraceAsString(),
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
                // Utiliser formatPhoneNumber pour normaliser le numéro admin
                $phone = $this->formatPhoneNumber($appInfo->ccphone2, $appInfo->phone2);
                
                if ($phone && $this->isValidPhoneNumber($phone)) {
                    $this->sendSms([$phone], $message);
                    Log::info('SMS admin envoyé', ['phone' => $phone]);
                } else {
                    Log::warning('SMS admin non envoyé: numéro invalide après formatage', [
                        'ccphone2' => $appInfo->ccphone2,
                        'phone2' => $appInfo->phone2,
                        'formatted' => $phone,
                    ]);
                }
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
