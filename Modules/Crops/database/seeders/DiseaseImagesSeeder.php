<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Crops\Models\Disease;

class DiseaseImagesSeeder extends Seeder
{
    private const UNSPLASH_ACCESS_KEY = 'KOqWUrbGoxbBRQ15V2SkG-9ZDZPGsWFjzb8nGwUtILU'; // Replace with actual key

    private const MIN_IMAGES = 3;

    private const MAX_IMAGES = 10;

    private const CSV_PATH = 'diseases.csv';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if Unsplash key is configured
        $accessKey = config('services.unsplash.access_key', self::UNSPLASH_ACCESS_KEY);
        if ($accessKey === 'YOUR_UNSPLASH_ACCESS_KEY') {
            $this->command?->error('Please configure your Unsplash access key in config/services.php');
            $this->command?->info('Add: \'unsplash\' => [\'access_key\' => \'your-actual-key\']');

            return;
        }

        $diseases = Disease::all();
        $updatedCount = 0;
        $csvData = $this->readCsvData();

        foreach ($diseases as $disease) {
            // Skip if already has images
            if (! empty($disease->images)) {
                $this->command?->info("Disease '{$disease->name['en']}' already has images, skipping...");

                continue;
            }

            $this->command?->info("Fetching images for: {$disease->name['en']}");

            $images = $this->fetchImagesFromUnsplash($disease->name['en'], $accessKey);

            if (! empty($images)) {
                $disease->images = $images;
                $disease->save();

                // Update CSV data
                $this->updateCsvRow($csvData, $disease->slug, $images);

                $updatedCount++;
                $this->command?->info('Added '.count($images)." images to {$disease->name['en']}");

                // Rate limiting - Unsplash free tier allows 50 requests per hour
                sleep(2); // Wait 2 seconds between requests
            } else {
                $this->command?->warn("No images found for: {$disease->name['en']}");
            }
        }

        // Write updated CSV data
        if ($updatedCount > 0) {
            $this->writeCsvData($csvData);
            $this->command?->info('Updated CSV file with image URLs');
        }

        $this->command?->info("Updated {$updatedCount} diseases with images from Unsplash");
    }

    private function fetchImagesFromUnsplash(string $query, string $accessKey): array
    {
        try {
            // Search for plant disease related images
            $searchQuery = "plant disease {$query}";

            $response = Http::withHeaders([
                'Authorization' => 'Client-ID '.$accessKey,
            ])->get('https://api.unsplash.com/search/photos', [
                'query' => $searchQuery,
                'per_page' => self::MAX_IMAGES,
                'orientation' => 'landscape',
            ]);

            if (! $response->successful()) {
                Log::warning("Unsplash API error for query '{$searchQuery}': ".$response->status());

                // Fallback to more generic search
                $response = Http::withHeaders([
                    'Authorization' => 'Client-ID '.$accessKey,
                ])->get('https://api.unsplash.com/search/photos', [
                    'query' => $query.' agriculture',
                    'per_page' => self::MAX_IMAGES,
                    'orientation' => 'landscape',
                ]);
            }

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                $images = [];

                foreach ($results as $result) {
                    // Use regular quality URL (not raw for bandwidth)
                    $images[] = $result['urls']['regular'] ?? $result['urls']['full'];

                    if (count($images) >= self::MAX_IMAGES) {
                        break;
                    }
                }

                // Ensure minimum number of images
                if (count($images) < self::MIN_IMAGES && count($images) > 0) {
                    // Duplicate some images to meet minimum
                    while (count($images) < self::MIN_IMAGES) {
                        $images[] = $images[array_rand($images)];
                    }
                }

                return array_slice($images, 0, rand(self::MIN_IMAGES, min(count($images), self::MAX_IMAGES)));
            }
        } catch (Exception $e) {
            Log::error('Error fetching images from Unsplash: '.$e->getMessage());
        }

        return [];
    }

    private function readCsvData(): array
    {
        $path = base_path(self::CSV_PATH);
        if (! file_exists($path)) {
            return [];
        }

        $data = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $data[] = $headers; // Keep headers

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }

        fclose($handle);

        return $data;
    }

    private function updateCsvRow(array &$csvData, string $slug, array $images): void
    {
        if (empty($csvData)) {
            return;
        }

        $headers = $csvData[0];
        $nameEnIndex = array_search('name_en', $headers);
        $imagesIndex = array_search('images', $headers);

        if ($nameEnIndex === false || $imagesIndex === false) {
            return;
        }

        // Find the row by matching slug with name_en
        for ($i = 1; $i < count($csvData); $i++) {
            $rowNameEn = $csvData[$i][$nameEnIndex] ?? '';
            if (\Illuminate\Support\Str::slug($rowNameEn) === $slug) {
                // Update images column
                $csvData[$i][$imagesIndex] = '"'.implode(',', $images).'"';
                break;
            }
        }
    }

    private function writeCsvData(array $csvData): void
    {
        $path = base_path(self::CSV_PATH);
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return;
        }

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }
}
