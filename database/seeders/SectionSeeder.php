<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSliders();
        $this->seedHero1();
        $this->seedHero2();
        $this->seedAds();
    }

    /**
     * Seed les 3 slides du carousel principal
     */
    private function seedSliders(): void
    {
        $defaultSlides = [
            [
                'subtitle' => 'Boutique en ligne',
                'title' => "ÉQUIPEZ\nVOTRE CUISINE",
                'description' => 'Découvrez notre sélection de mixeurs, vaisselle, thermos et accessoires de cuisine de qualité.',
                'btn' => 'Acheter maintenant',
                'link' => '/produits',
            ],
            [
                'subtitle' => 'Collection exclusive',
                'title' => "VAISSELLE\nPREMIUM",
                'description' => 'Des assiettes, verres et couverts élégants pour sublimer votre table au quotidien.',
                'btn' => 'Découvrir',
                'link' => '/produits',
            ],
            [
                'subtitle' => 'Qualité garantie',
                'title' => "THERMOS &\nACCESSOIRES",
                'description' => 'Gardez vos boissons chaudes ou froides avec nos thermos et accessoires durables.',
                'btn' => 'Voir la collection',
                'link' => '/produits',
            ],
        ];

        // Récupérer les sliders existants
        $existingSliders = Section::where('type', 'slider')->get();
        $existingCount = $existingSliders->count();

        Log::info("SectionSeeder: {$existingCount} sliders existants trouvés");

        foreach ($defaultSlides as $index => $slideData) {
            if ($index < $existingCount) {
                // Mettre à jour le slider existant
                $slider = $existingSliders[$index];
                $slider->update([
                    'subtitle' => $slideData['subtitle'],
                    'title' => $slideData['title'],
                    'description' => $slideData['description'],
                    'btn' => $slideData['btn'],
                    'link' => $slideData['link'],
                    // On garde image1 et image2 existants
                ]);
                Log::info("SectionSeeder: Slider #{$slider->id} mis à jour");
            } else {
                // Créer un nouveau slider
                $slider = Section::create([
                    'type' => 'slider',
                    'subtitle' => $slideData['subtitle'],
                    'title' => $slideData['title'],
                    'description' => $slideData['description'],
                    'btn' => $slideData['btn'],
                    'link' => $slideData['link'],
                    'image1' => null,
                    'image2' => null,
                ]);
                Log::info("SectionSeeder: Nouveau slider créé (ID: {$slider->id})");
            }
        }

        $this->command->info("✓ Sliders: {$existingCount} mis à jour, " . max(0, count($defaultSlides) - $existingCount) . " créés");
    }

    /**
     * Seed la carte Hero 1 (colonne droite, haut)
     */
    private function seedHero1(): void
    {
        $defaultHero1 = [
            'subtitle' => 'NOUVEAU',
            'title' => 'Mixeurs Pro',
            'description' => 'Nouveautés 2025',
            'btn' => 'Voir plus',
            'link' => '/produits',
        ];

        $hero1 = Section::where('type', 'hero1')->first();

        if ($hero1) {
            $hero1->update($defaultHero1);
            $this->command->info("✓ Hero1: Mis à jour (ID: {$hero1->id})");
        } else {
            $hero1 = Section::create(array_merge(['type' => 'hero1'], $defaultHero1));
            $this->command->info("✓ Hero1: Créé (ID: {$hero1->id})");
        }
    }

    /**
     * Seed la carte Hero 2 (colonne droite, bas)
     */
    private function seedHero2(): void
    {
        $defaultHero2 = [
            'subtitle' => '-30%',
            'title' => "Jusqu'à 30%",
            'description' => 'Vaisselle Premium',
            'btn' => 'Voir plus',
            'link' => '/produits',
        ];

        $hero2 = Section::where('type', 'hero2')->first();

        if ($hero2) {
            $hero2->update($defaultHero2);
            $this->command->info("✓ Hero2: Mis à jour (ID: {$hero2->id})");
        } else {
            $hero2 = Section::create(array_merge(['type' => 'hero2'], $defaultHero2));
            $this->command->info("✓ Hero2: Créé (ID: {$hero2->id})");
        }
    }

    /**
     * Seed les emplacements publicitaires (ads1 à ads7)
     * Crée des placeholders vides si inexistants
     */
    private function seedAds(): void
    {
        $adsTypes = ['ads1', 'ads2', 'ads3', 'ads4', 'ads5', 'ads6', 'ads7'];
        $created = 0;
        $existing = 0;

        foreach ($adsTypes as $adType) {
            $ad = Section::where('type', $adType)->first();

            if (!$ad) {
                Section::create([
                    'type' => $adType,
                    'title' => ucfirst($adType) . ' - Emplacement publicitaire',
                    'subtitle' => 'Publicité',
                    'description' => 'Configurez cette publicité depuis le back-office',
                    'btn' => 'En savoir plus',
                    'link' => '/produits',
                    'image1' => null,
                    'image2' => null,
                ]);
                $created++;
            } else {
                $existing++;
            }
        }

        $this->command->info("✓ Ads: {$existing} existants, {$created} créés");
    }
}
