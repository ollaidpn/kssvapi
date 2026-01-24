<?php

namespace App\Helpers;

use App\Services\NotificationsService;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class Shortcut
{
    /**
     * Vérifie si un fichier existe sur le serveur
     * Retourne l'URL de l'asset ou no-image.png par défaut
     *
     * @param string|null $path Chemin relatif (ex: 'uploads/avatar.jpg')
     * @return string URL complète de l'image
     */
    public static function fileExistsOnServer(?string $path): string
    {
        if (empty($path)) {
            return asset('no-image.png');
        }

        // Chemin absolu sur le serveur
        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            return asset($path);
        }

        return asset('no-image.png');
    }

    /**
     * Envoyer un SMS à un destinataire
     *
     * @param string $phone Numéro au format international (+221XXXXXXXXX)
     * @param string $message Contenu du message
     * @return array
     */
    public static function sendSMS(string $phone, string $message): array
    {
        $service = new NotificationsService();
        return $service->sendSms([$phone], $message);
    }

    /**
     * Retourne le montant à envoyer à Fayko (mode TEST = 10 FCFA)
     * Le montant est toujours converti en integer
     *
     * @param float $amount Montant original
     * @return int Montant à envoyer à Fayko
     */
    public static function getFaykoAmount(float $amount): int
    {
        $mode = config('services.fayko.mode', 'LIVE');
        
        if (strtoupper($mode) === 'TEST') {
            return 10; // 10 FCFA pour les tests
        }
        
        // Toujours retourner un integer (Fayko n'accepte pas les décimaux)
        return (int) round($amount);
    }

    /**
     * Vérifie si Fayko est en mode test
     *
     * @return bool
     */
    public static function isFaykoTestMode(): bool
    {
        return strtoupper(config('services.fayko.mode', 'LIVE')) === 'TEST';
    }

    /**
     * Récupère l'URL du frontend automatiquement depuis la requête
     * Fallback sur config('app.frontend_url') si non disponible
     *
     * @param Request|null $request
     * @return string
     */
    public static function getFrontendUrl(?Request $request = null): string
    {
        // Essayer Origin header
        if ($request && $request->header('Origin')) {
            return rtrim($request->header('Origin'), '/');
        }
        
        // Essayer Referer header
        if ($request && $request->header('Referer')) {
            $parsed = parse_url($request->header('Referer'));
            if (isset($parsed['scheme']) && isset($parsed['host'])) {
                $url = $parsed['scheme'] . '://' . $parsed['host'];
                if (isset($parsed['port']) && $parsed['port'] != 80 && $parsed['port'] != 443) {
                    $url .= ':' . $parsed['port'];
                }
                return $url;
            }
        }
        
        // Fallback config
        return config('app.frontend_url', config('app.url'));
    }
}
