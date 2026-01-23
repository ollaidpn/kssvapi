<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{
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
                $data = $query->get();
                Log::debug('API Sections: Sections recuperees (multiple)', ['type' => $type, 'count' => $data->count()]);
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            $data = $query->first();
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
                'slider' => Section::where('type', 'slider')->get(),
                'hero1' => Section::where('type', 'hero1')->first(),
                'hero2' => Section::where('type', 'hero2')->first(),
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
                'ads1' => Section::where('type', 'ads1')->first(),
                'ads2' => Section::where('type', 'ads2')->first(),
                'ads3' => Section::where('type', 'ads3')->first(),
                'ads4' => Section::where('type', 'ads4')->first(),
                'ads5' => Section::where('type', 'ads5')->first(),
                'ads6' => Section::where('type', 'ads6')->first(),
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
