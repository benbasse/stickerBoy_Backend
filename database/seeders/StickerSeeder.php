<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Sticker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StickerSeeder extends Seeder
{
    /**
     * Seed the application's database with stickers from the shoot stickers folder.
     */
    public function run(): void
    {
        $basePath = public_path('shoot stickers');

        if (!File::isDirectory($basePath)) {
            $this->command->error("Le dossier 'shoot stickers' n'existe pas dans public/");
            return;
        }

        // Créer le dossier stickers dans storage s'il n'existe pas
        if (!Storage::disk('public')->exists('stickers')) {
            Storage::disk('public')->makeDirectory('stickers');
        }

        $directories = File::directories($basePath);
        $totalStickers = 0;

        foreach ($directories as $directory) {
            $categoryName = basename($directory);

            // Créer ou récupérer la catégorie
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['description' => "Catégorie {$categoryName}"]
            );

            $this->command->info("Traitement de la catégorie: {$categoryName}");

            // Récupérer toutes les images du dossier
            $files = File::files($directory);
            $categoryStickers = 0;

            foreach ($files as $file) {
                $extension = strtolower($file->getExtension());

                // Vérifier si c'est une image
                if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
                    continue;
                }

                $filename = $file->getFilename();
                $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

                // Générer un nom propre pour le sticker
                $stickerName = $this->cleanStickerName($filenameWithoutExt, $categoryName);

                // Générer un nom unique pour éviter les conflits
                $uniqueFilename = Str::uuid() . '.' . $extension;
                $storagePath = 'stickers/' . $uniqueFilename;

                // Copier l'image vers storage/app/public/stickers/
                $imageContent = File::get($file->getPathname());
                Storage::disk('public')->put($storagePath, $imageContent);

                // Créer le sticker avec le chemin storage
                Sticker::create([
                    'name' => $stickerName,
                    'image' => $storagePath,
                    'category_id' => $category->id,
                    'sub_category_id' => null,
                    'price' => 200,
                    'description' => null,
                    'quantity' => 30,
                    'taille' => 'Standard',
                ]);

                $categoryStickers++;
                $totalStickers++;
            }

            $this->command->info("  -> {$categoryStickers} stickers ajoutés");
        }

        $this->command->info("Total: {$totalStickers} stickers importés avec succès!");
    }

    /**
     * Nettoyer le nom du sticker
     */
    private function cleanStickerName(string $filename, string $categoryName): string
    {
        // Supprimer les patterns communs comme "NOM ... CATEGORIE ... QUANTITE ..."
        $name = preg_replace('/NOM\s+/i', '', $filename);
        $name = preg_replace('/\s*CATEGORIE\s+.*/i', '', $name);
        $name = preg_replace('/\s*QUANTITE\s+\d+.*/i', '', $name);

        // Supprimer les numéros entre parenthèses à la fin (ex: "(2)")
        $name = preg_replace('/\s*\(\d+\)\s*$/', '', $name);

        // Supprimer "Capture d'écran" et les dates
        $name = preg_replace('/Capture d\'écran \d{4}-\d{2}-\d{2} \d+/', '', $name);

        // Nettoyer les espaces multiples
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);

        // Si le nom est vide après nettoyage, utiliser le nom de la catégorie + un identifiant unique
        if (empty($name)) {
            $name = $categoryName . ' ' . Str::random(4);
        }

        return $name;
    }
}
