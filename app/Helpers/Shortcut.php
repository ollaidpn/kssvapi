<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

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
}
