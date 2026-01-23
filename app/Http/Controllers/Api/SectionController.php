<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

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
        $type = $request->get('type');
        $mode = $request->get('mode', 'single'); // single ou multiple

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Le paramètre "type" est requis'
            ], 400);
        }

        $query = Section::where('type', $type);

        if ($mode === 'multiple') {
            return response()->json([
                'success' => true,
                'data' => $query->get()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $query->first()
        ]);
    }

    /**
     * API combinée pour récupérer toutes les sections du Hero (slider + hero1 + hero2)
     * UN SEUL appel API pour tout le Hero
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHeroSection()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'slider' => Section::where('type', 'slider')->get(),
                'hero1' => Section::where('type', 'hero1')->first(),
                'hero2' => Section::where('type', 'hero2')->first(),
            ]
        ]);
    }

    /**
     * API combinée pour récupérer toutes les publicités (ads1 à ads6)
     * UN SEUL appel API pour toutes les pubs
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdsSection()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'ads1' => Section::where('type', 'ads1')->first(),
                'ads2' => Section::where('type', 'ads2')->first(),
                'ads3' => Section::where('type', 'ads3')->first(),
                'ads4' => Section::where('type', 'ads4')->first(),
                'ads5' => Section::where('type', 'ads5')->first(),
                'ads6' => Section::where('type', 'ads6')->first(),
            ]
        ]);
    }
}
