<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageOptimizationService
{
    private int $maxWidth;

    private int $maxHeight;

    private int $quality;

    public function __construct()
    {
        $this->maxWidth = config('plantscanner.image.max_width', 1024);
        $this->maxHeight = config('plantscanner.image.max_height', 1024);
        $this->quality = config('plantscanner.image.quality', 85);
    }

    /**
     * @throws Exception
     */
    public function optimize(UploadedFile $image): array
    {
        $originalPath = $this->storeOriginal($image);
        $optimizedPath = $this->createOptimized($image, $originalPath);
        $imageHash = $this->hashImage($image);

        return [
            'original_path' => $originalPath,
            'optimized_path' => $optimizedPath,
            'image_hash' => $imageHash,
        ];
    }

    public function hashImage(UploadedFile $image): string
    {
        return hash_file('sha256', $image->getRealPath());
    }

    public function deleteOriginalImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function optimizeFromPath(string $filePath, string $storagePath): array
    {
        // Create a temporary UploadedFile-like object
        $originalPath = $storagePath;
        $optimizedPath = $this->createOptimizedFromPath($filePath, $originalPath);

        return [
            'original_path' => $originalPath,
            'optimized_path' => $optimizedPath,
        ];
    }

    private function createOptimizedFromPath(string $filePath, string $originalPath): string
    {
        $sourceImage = $this->createImageFromFile($filePath);
        if (! $sourceImage) {
            throw new Exception('Failed to create image from file path');
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        [$newWidth, $newHeight] = $this->calculateDimensions($originalWidth, $originalHeight);

        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($optimizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        $optimizedPath = str_replace('originals', 'optimized', $originalPath);
        $optimizedPath = preg_replace('/\.[^.]+$/', '.jpg', $optimizedPath);

        $fullPath = Storage::disk('public')->path($optimizedPath);
        $directory = dirname($fullPath);

        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        imagejpeg($optimizedImage, $fullPath, $this->quality);

        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);

        return $optimizedPath;
    }

    private function storeOriginal(UploadedFile $image): string
    {
        $path = 'plant-scans/originals/'.date('Y/m/d');

        return $image->store($path, 'public');
    }

    private function createOptimized(UploadedFile $image, string $originalPath): string
    {
        $sourceImage = $this->createImageFromFile($image->getRealPath());
        if (! $sourceImage) {
            throw new Exception('Failed to create image from uploaded file');
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        [$newWidth, $newHeight] = $this->calculateDimensions($originalWidth, $originalHeight);

        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($optimizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        $optimizedPath = str_replace('originals', 'optimized', $originalPath);
        $optimizedPath = preg_replace('/\.[^.]+$/', '.jpg', $optimizedPath);

        $fullPath = Storage::disk('public')->path($optimizedPath);
        $directory = dirname($fullPath);

        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        imagejpeg($optimizedImage, $fullPath, $this->quality);

        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);

        return $optimizedPath;
    }

    private function createImageFromFile(string $filePath)
    {
        $imageInfo = getimagesize($filePath);
        if (! $imageInfo) {
            return false;
        }

        return match ($imageInfo['mime']) {
            'image/jpeg' => imagecreatefromjpeg($filePath),
            'image/png' => imagecreatefrompng($filePath),
            'image/gif' => imagecreatefromgif($filePath),
            'image/webp' => imagecreatefromwebp($filePath),
            default => false,
        };
    }

    private function calculateDimensions(int $originalWidth, int $originalHeight): array
    {
        if ($originalWidth <= $this->maxWidth && $originalHeight <= $this->maxHeight) {
            return [$originalWidth, $originalHeight];
        }

        $ratio = min($this->maxWidth / $originalWidth, $this->maxHeight / $originalHeight);

        return [
            (int) round($originalWidth * $ratio),
            (int) round($originalHeight * $ratio),
        ];
    }

    public function getOptimizedImageContent(string $path): string
    {
        return Storage::disk('public')->get($path);
    }
}
