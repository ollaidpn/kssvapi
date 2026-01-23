<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Helpers\Shortcut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{
    /**
     * Transforme une section en ajoutant les URLs d'images vérifiées
     *
     * @param Section|null $section
     * @return Section|null
     */
    private function transformSection(?Section $section): ?Section
    {
        if (!$section) return null;
        
        $section->image1 = Shortcut::fileExistsOnServer($section->image1);
        $section->image2 = Shortcut::fileExistsOnServer($section->image2);
        
        return $section;
    }

    /**
     * API générique pour récupérer des sections par type
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByType(Request $request)
    {
        try {
            $type = $request->get('type');
            $mode = $request->get('mode', 'single'); // single ou multiple

            Log::debug('API Sections: Recuperation par type', ['type' => $type, 'mode' => $mode]);

            if (!$type) {
                Log::warning('API Sections: Parametre type manquant');
                return response()->json([
                    'success' => false,
                    'message' => 'Le paramètre "type" est requis'
                ], 400);
            }

            $query = Section::where('type', $type);

            if ($mode === 'multiple') {
                $data = $query->get()->map(fn($s) => $this->transformSection($s));
                Log::debug('API Sections: Sections recuperees (multiple)', ['type' => $type, 'count' => $data->count()]);
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            $data = $this->transformSection($query->first());
            Log::debug('API Sections: Section recuperee (single)', ['type' => $type, 'found' => $data !== null]);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('API Sections: Erreur recuperation par type', [
                'type' => $request->get('type'),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des sections'
            ], 500);
        }
    }

    /**
     * API combinée pour récupérer toutes les sections du Hero (slider + hero1 + hero2)
     * UN SEUL appel API pour tout le Hero
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHeroSection()
    {
        try {
            Log::debug('API Sections: Chargement Hero Section');
            
            $data = [
                'slider' => Section::where('type', 'slider')->get()->map(fn($s) => $this->transformSection($s)),
                'hero1' => $this->transformSection(Section::where('type', 'hero1')->first()),
                'hero2' => $this->transformSection(Section::where('type', 'hero2')->first()),
            ];
            
            Log::info('API Sections: Hero Section charge', [
                'slider_count' => $data['slider']->count(),
                'hero1_exists' => $data['hero1'] !== null,
                'hero2_exists' => $data['hero2'] !== null
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('API Sections: Erreur chargement Hero Section', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du Hero'
            ], 500);
        }
    }

    /**
     * API combinée pour récupérer toutes les publicités (ads1 à ads6)
     * UN SEUL appel API pour toutes les pubs
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdsSection()
    {
        try {
            Log::debug('API Sections: Chargement Ads Section');
            
            $data = [
                'ads1' => $this->transformSection(Section::where('type', 'ads1')->first()),
                'ads2' => $this->transformSection(Section::where('type', 'ads2')->first()),
                'ads3' => $this->transformSection(Section::where('type', 'ads3')->first()),
                'ads4' => $this->transformSection(Section::where('type', 'ads4')->first()),
                'ads5' => $this->transformSection(Section::where('type', 'ads5')->first()),
                'ads6' => $this->transformSection(Section::where('type', 'ads6')->first()),
            ];
            
            $adsLoaded = collect($data)->filter(fn($ad) => $ad !== null)->count();
            Log::info('API Sections: Ads Section charge', ['ads_loaded' => $adsLoaded]);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('API Sections: Erreur chargement Ads Section', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des publicités'
            ], 500);
        }
    }
}
