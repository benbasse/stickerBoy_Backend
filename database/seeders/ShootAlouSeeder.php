<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Sticker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShootAlouSeeder extends Seeder
{
    /**
     * Seed stickers from SHOOT ALOU folder.
     *
     * Format des fichiers: NOM {nom}   CATEGORIE {categorie} QUANTITE {quantite} ({numero}).jpeg
     */
    public function run(): void
    {
        $basePath = storage_path('app/public/stickers/SHOOT ALOU');

        if (!File::isDirectory($basePath)) {
            $this->command->error("Le dossier 'SHOOT ALOU' n'existe pas dans storage/app/public/stickers/");
            return;
        }

        $files = File::files($basePath);
        $totalStickers = 0;
        $categoriesCount = [];

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());

            // Vérifier si c'est une image
            if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
                continue;
            }

            $filename = $file->getFilename();

            // Parser le nom du fichier
            $parsed = $this->parseFilename($filename);

            if (!$parsed) {
                $this->command->warn("Impossible de parser: {$filename}");
                continue;
            }

            // Normaliser le nom de la catégorie
            $categoryName = $this->normalizeCategoryName($parsed['category']);

            // Créer ou récupérer la catégorie
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['description' => "Catégorie {$categoryName}"]
            );

            // Générer un nom unique pour le fichier dans storage
            $uniqueFilename = Str::uuid() . '.' . $extension;
            $newStoragePath = 'stickers/' . $uniqueFilename;

            // Copier l'image vers le nouveau chemin
            $imageContent = File::get($file->getPathname());
            Storage::disk('public')->put($newStoragePath, $imageContent);

            // Créer le sticker
            Sticker::create([
                'name' => $parsed['name'],
                'image' => $newStoragePath,
                'category_id' => $category->id,
                'sub_category_id' => null,
                'price' => 200,
                'description' => null,
                'quantity' => $parsed['quantity'],
                'taille' => 'Standard',
            ]);

            // Comptage par catégorie
            if (!isset($categoriesCount[$categoryName])) {
                $categoriesCount[$categoryName] = 0;
            }
            $categoriesCount[$categoryName]++;
            $totalStickers++;
        }

        // Afficher le résumé
        $this->command->info("=== Résumé de l'import ===");
        foreach ($categoriesCount as $cat => $count) {
            $this->command->info("  {$cat}: {$count} stickers");
        }
        $this->command->info("Total: {$totalStickers} stickers importés avec succès!");
    }

    /**
     * Parse le nom du fichier pour extraire les informations.
     * Format: NOM {nom}   CATEGORIE {categorie} QUANTITE {quantite} ({numero}).jpeg
     */
    private function parseFilename(string $filename): ?array
    {
        // Pattern pour extraire: NOM xxx CATEGORIE yyy QUANTITE zzz
        $pattern = '/^NOM\s+(.+?)\s+CATEGORIE\s+(.+?)\s+QUANTITE\s+(\d+)/i';

        if (preg_match($pattern, $filename, $matches)) {
            return [
                'name' => trim($matches[1]),
                'category' => trim($matches[2]),
                'quantity' => (int) $matches[3],
            ];
        }

        // Pattern alternatif sans espace après NOM (ex: NOMBALEINE)
        $pattern2 = '/^NOM([A-Z]+)\s+CATEGORIE\s+(.+?)\s+QUANTITE\s+(\d+)/i';

        if (preg_match($pattern2, $filename, $matches)) {
            return [
                'name' => trim($matches[1]),
                'category' => trim($matches[2]),
                'quantity' => (int) $matches[3],
            ];
        }

        return null;
    }

    /**
     * Normalise le nom de la catégorie pour correspondre aux catégories existantes.
     */
    private function normalizeCategoryName(string $category): string
    {
        $category = strtoupper(trim($category));

        // Mapping des variations vers les noms standard
        $mapping = [
            'MANGA' => 'MAnga',
            'GIRLY' => 'Girly',
            'ART' => 'ART',
            'BOYS' => 'Boys',
            'BOY' => 'Boys',
            'BIKE&CAR' => 'BIKE&CAR',
            'DRIP' => 'Drip perso',
            'MOTIVATION' => 'Motivation',
            'ESPACE' => 'Espace',
            'MARVEL' => 'Marvel', // Nouvelle catégorie
            'RELIGION' => 'Religion',
        ];

        return $mapping[$category] ?? ucfirst(strtolower($category));
    }
}
