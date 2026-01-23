<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageDownloadService
{
    /**
     * Télécharge une image depuis une URL externe et la sauvegarde en local
     * 
     * @param string $url URL de l'image externe
     * @param string $folder Dossier de destination (ex: 'items', 'categories')
     * @return string|null Chemin relatif de l'image sauvegardée
     */
    public function downloadImage(string $url, string $folder = 'items'): ?string
    {
        try {
            // Vérifier que l'URL est valide
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                Log::warning('ImageDownloadService: URL invalide', ['url' => $url]);
                return null;
            }
            
            // Ignorer les images placeholder
            if ($this->isPlaceholderImage($url)) {
                Log::info('ImageDownloadService: Image placeholder ignorée', ['url' => $url]);
                return null;
            }
            
            // Récupérer l'image avec timeout
            $response = Http::timeout(30)
                ->withOptions(['verify' => false]) // Ignorer SSL pour HomeIP
                ->get($url);
            
            if (!$response->successful()) {
                Log::warning('ImageDownloadService: Échec téléchargement', [
                    'url' => $url,
                    'status' => $response->status()
                ]);
                return null;
            }
            
            // Vérifier que c'est bien une image
            $contentType = $response->header('Content-Type');
            if ($contentType && !str_contains($contentType, 'image/')) {
                Log::warning('ImageDownloadService: Pas une image', [
                    'url' => $url,
                    'content_type' => $contentType
                ]);
                return null;
            }
            
            // Déterminer l'extension
            $extension = $this->getExtensionFromContentType($contentType) 
                ?? $this->getExtensionFromUrl($url) 
                ?? 'jpg';
            
            // Générer un nom unique
            $filename = $folder . '/' . Str::uuid() . '.' . $extension;
            
            // Chemin complet dans le dossier public
            $fullPath = public_path('uploads/' . $filename);
            
            // Créer le dossier si nécessaire
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Écrire le fichier
            file_put_contents($fullPath, $response->body());
            
            Log::info('ImageDownloadService: Image téléchargée', [
                'url' => $url,
                'local_path' => 'uploads/' . $filename
            ]);
            
            return 'uploads/' . $filename;
            
        } catch (\Exception $e) {
            Log::error('ImageDownloadService: Erreur téléchargement', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Télécharge plusieurs images
     * 
     * @param array $urls Liste des URLs à télécharger
     * @param string $folder Dossier de destination
     * @return array Liste des chemins locaux des images téléchargées
     */
    public function downloadImages(array $urls, string $folder = 'items'): array
    {
        $localPaths = [];
        
        foreach ($urls as $url) {
            if (empty($url)) {
                continue;
            }
            
            $path = $this->downloadImage($url, $folder);
            if ($path) {
                $localPaths[] = $path;
            }
        }
        
        return $localPaths;
    }
    
    /**
     * Extrait l'extension depuis le Content-Type
     */
    private function getExtensionFromContentType(?string $contentType): ?string
    {
        if (!$contentType) {
            return null;
        }
        
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        
        foreach ($map as $type => $ext) {
            if (str_contains($contentType, $type)) {
                return $ext;
            }
        }
        
        return null;
    }
    
    /**
     * Extrait l'extension depuis l'URL
     */
    private function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }
        
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        return in_array($extension, $validExtensions) ? $extension : null;
    }
    
    /**
     * Vérifie si l'URL est une image placeholder à ignorer
     */
    private function isPlaceholderImage(string $url): bool
    {
        $placeholders = [
            'aucune.png',      // Format HomeIP réel
            'aucune.jpg',
            'aucunimage.png',
            'aucunimage.jpg',
            'aucun_image.png',
            'aucun_image.jpg',
            'noimage.png',
            'noimage.jpg',
            'no_image.png',
            'no_image.jpg',
            'no-image.png',
            'no-image.jpg',
            'placeholder.png',
            'placeholder.jpg',
            'default.png',
            'default.jpg',
        ];
        
        $urlLower = strtolower($url);
        foreach ($placeholders as $placeholder) {
            if (str_contains($urlLower, $placeholder)) {
                return true;
            }
        }
        
        return false;
    }
}
