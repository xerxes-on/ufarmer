<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\Pest;

class CsvPestsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = base_path('pests.csv');

        // Check if file with images exists first
        $pathWithImages = base_path('pests_with_images.csv');
        if (file_exists($pathWithImages)) {
            $path = $pathWithImages;
        }

        if (! file_exists($path)) {
            $this->command?->warn("pests.csv not found at: {$path}");

            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command?->error('Unable to open pests.csv for reading');

            return;
        }

        $headers = fgetcsv($handle); // read header row
        if ($headers === false) {
            fclose($handle);
            $this->command?->error('pests.csv appears to be empty');

            return;
        }

        // Normalize headers to associative indexes
        $headers = array_map(static function ($h) {
            return trim((string) $h);
        }, $headers);

        $count = 0;
        $sort = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            foreach ($headers as $i => $key) {
                $data[$key] = $row[$i] ?? null;
            }

            // Map CSV fields to model attributes
            $nameUz = trim((string) ($data['name'] ?? ''));
            $nameRu = trim((string) ($data['name_ru'] ?? ''));
            $nameEn = trim((string) ($data['name_en'] ?? ''));

            $descUz = trim((string) ($data['description'] ?? ''));
            $descRu = trim((string) ($data['description_ru'] ?? ''));
            $descEn = trim((string) ($data['description_en'] ?? ''));

            // Build JSON fields
            $name = [
                'uz' => $nameUz ?: ($nameEn ?: $nameRu),
                'ru' => $nameRu ?: $nameUz,
                'en' => $nameEn ?: $nameUz,
            ];

            $description = [
                'uz' => $descUz ?: 'Zararkunanda.',
                'ru' => $descRu ?: 'Вредитель.',
                'en' => $descEn ?: 'Pest.',
            ];

            // Use English name as unique identifier
            $uniqueName = $name['en'] ?: $name['uz'] ?: $name['ru'];

            // Handle images field - convert string to array if needed
            $images = trim((string) ($data['images'] ?? ''));
            if ($images && $images !== '""' && $images !== "''") {
                // Check if it's already JSON
                if (str_starts_with($images, '[') && str_ends_with($images, ']')) {
                    $imagesArray = json_decode($images, true) ?: [];
                } else {
                    // Remove quotes if present
                    $images = trim($images, '"\'');
                    // Split by comma if multiple URLs
                    if (str_contains($images, ',')) {
                        $imagesArray = array_map('trim', explode(',', $images));
                    } else {
                        $imagesArray = [$images];
                    }
                }
                // Filter out empty values
                $imagesArray = array_filter($imagesArray);
            } else {
                $imagesArray = null;
            }

            $pest = Pest::updateOrCreate(
                ['name->en' => $uniqueName],
                [
                    'name' => $name,
                    'description' => $description,
                    'biology_name' => trim((string) ($data['biology_name'] ?? '')) ?: null,
                    'images' => $imagesArray,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );

            // Handle crop relationships
            $cropsString = trim((string) ($data['crops'] ?? ''));
            if ($cropsString) {
                $cropNames = array_map('trim', explode(',', $cropsString));
                $cropIds = [];

                foreach ($cropNames as $cropName) {
                    if (empty($cropName)) {
                        continue;
                    }

                    // Try to find crop by name
                    $crop = Crop::whereJsonContains('name->en', $cropName)
                        ->orWhereJsonContains('name->uz', $cropName)
                        ->orWhereJsonContains('name->ru', $cropName)
                        ->first();

                    if ($crop) {
                        $cropIds[] = $crop->id;
                    } else {
                        $this->command?->warn("Crop not found for pest '{$nameEn}': {$cropName}");
                    }
                }

                if (! empty($cropIds)) {
                    $pest->crops()->sync($cropIds);
                }
            }

            $count++;
        }

        fclose($handle);

        $this->command?->info("Imported/updated {$count} pests from pests.csv");
    }
}
