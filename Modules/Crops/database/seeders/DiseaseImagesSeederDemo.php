<?php

declare(strict_types=1);

namespace Modules\Crops\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Crops\Models\Disease;

class DiseaseImagesSeederDemo extends Seeder
{
    private const MIN_IMAGES = 3;

    private const MAX_IMAGES = 10;

    private const CSV_PATH = 'diseases.csv';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Running demo version with placeholder images...');

        $diseases = Disease::all();
        $updatedCount = 0;
        $csvData = $this->readCsvData();

        foreach ($diseases as $disease) {
            // Skip if already has images
            if (! empty($disease->images)) {
                $this->command?->info("Disease '{$disease->name['en']}' already has images, skipping...");

                continue;
            }

            $this->command?->info("Generating placeholder images for: {$disease->name['en']}");

            // Generate placeholder image URLs
            $numImages = rand(self::MIN_IMAGES, self::MAX_IMAGES);
            $images = [];

            for ($i = 0; $i < $numImages; $i++) {
                // Using picsum.photos for placeholder images
                $width = 800 + ($i * 50); // Vary dimensions slightly
                $height = 600 + ($i * 30);
                $seed = urlencode($disease->name['en'].'_'.$i);
                $images[] = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
            }

            if (! empty($images)) {
                $disease->images = $images;
                $disease->save();

                // Update CSV data
                $this->updateCsvRow($csvData, $disease->slug, $images);

                $updatedCount++;
                $this->command?->info('Added '.count($images)." placeholder images to {$disease->name['en']}");
            }
        }

        // Write updated CSV data
        if ($updatedCount > 0) {
            $this->writeCsvData($csvData);
            $this->command?->info('Updated CSV file with image URLs');
        }

        $this->command?->info("Updated {$updatedCount} diseases with placeholder images");
        $this->command?->warn('Note: Replace with real Unsplash images by configuring UNSPLASH_ACCESS_KEY in .env');
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
