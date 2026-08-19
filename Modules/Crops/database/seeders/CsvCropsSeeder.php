<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Crops\Models\Crop;

class CsvCropsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = base_path('crops.csv');

        if (! file_exists($path)) {
            $this->command?->warn("crops.csv not found at: {$path}");

            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command?->error('Unable to open crops.csv for reading');

            return;
        }

        $headers = fgetcsv($handle); // read header row
        if ($headers === false) {
            fclose($handle);
            $this->command?->error('crops.csv appears to be empty');

            return;
        }

        // Normalize headers to associative indexes
        $headers = array_map(static function ($h) {
            return trim((string) $h);
        }, $headers);

        $count = 0;
        $softDisabled = 0;
        $sort = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            foreach ($headers as $i => $key) {
                $data[$key] = $row[$i] ?? null;
            }

            $activeFlag = strtolower(trim((string) ($data['active'] ?? '')));
            $shouldSeed = in_array($activeFlag, ['1', 'true', 'yes', 'y'], true);

            // Map CSV fields to model attributes
            $nameUz = trim((string) ($data['name'] ?? ''));
            $nameRu = trim((string) ($data['name_ru'] ?? ''));
            $nameEn = trim((string) ($data['name_en'] ?? ''));

            $descUz = trim((string) ($data['description'] ?? ''));
            $descRu = trim((string) ($data['description_ru'] ?? ''));
            $descEn = trim((string) ($data['description_en'] ?? ''));

            $tempMinRaw = trim((string) ($data['recommended_temp_min'] ?? ''));
            $tempMaxRaw = trim((string) ($data['recommended_temp_max'] ?? ''));
            $tempMin = $tempMinRaw !== '' ? round((float) $tempMinRaw, 1) : null;
            $tempMax = $tempMaxRaw !== '' ? round((float) $tempMaxRaw, 1) : null;

            // Build JSON fields
            $name = [
                'uz' => $nameUz ?: ($nameEn ?: $nameRu),
                'ru' => $nameRu ?: $nameUz,
                'en' => $nameEn ?: ($data['biology_name'] ?? $nameUz),
            ];

            $description = [
                'uz' => $descUz ?: 'Kultura.',
                'ru' => $descRu ?: 'Культура.',
                'en' => $descEn ?: 'Crop.',
            ];

            // Use English name as unique identifier
            $uniqueName = $name['en'] ?: $name['uz'] ?: $name['ru'];

            if (! $shouldSeed) {
                Crop::query()
                    ->where('name->en', $uniqueName)
                    ->update(['is_active' => false]);
                $softDisabled++;

                continue;
            }

            Crop::updateOrCreate(
                ['name->en' => $uniqueName],
                [
                    'name' => $name,
                    'description' => $description,
                    'recommended_temp_min' => $tempMin,
                    'recommended_temp_max' => $tempMax,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ]
            );

            $count++;
        }

        fclose($handle);

        $message = "Imported/updated {$count} active crops from crops.csv";
        if ($softDisabled > 0) {
            $message .= "; marked {$softDisabled} as inactive";
        }

        $this->command?->info($message);
    }
}
