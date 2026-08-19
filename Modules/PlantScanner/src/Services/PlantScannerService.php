<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Services;

use Illuminate\Http\UploadedFile;
use Modules\Core\Models\User;
use Modules\PlantScanner\Enums\PlantScannerTranslationKey;
use Modules\PlantScanner\Enums\ScanStatus;
use Modules\PlantScanner\Models\ScannedPlant;
use Modules\PlantScanner\Services\AI\AiAnalysisService;

class PlantScannerService
{
    public function __construct(
        private readonly ImageOptimizationService $imageService,
        private readonly AiAnalysisService $aiService
    ) {}

    public function scan(UploadedFile $image, User $user, ?string $aiProvider = null, ?string $language = null): array
    {
        $imageHash = $this->imageService->hashImage($image);

        $existingScan = $this->checkForDuplicate($user->id, $imageHash);
        if ($existingScan) {
            return [
                'success' => true,
                'duplicate' => true,
                'scan' => $existingScan,
            ];
        }

        $optimization = $this->imageService->optimize($image);
        $optimizedPath = $optimization['optimized_path'];
        $base64Image = base64_encode($this->imageService->getOptimizedImageContent($optimizedPath));

        $this->extendExecutionTime();

        $defaultProvider = config('plantscanner.ai.default_provider', 'openrouter');
        $scan = $this->createScan($user->id, $aiProvider ?? $defaultProvider, $optimizedPath, $optimization['original_path'], $imageHash);

        $analysis = $this->aiService->analyze($base64Image, [
            'language' => $language ?? config('plantscanner.ai.language', 'en'),
            'model' => config('plantscanner.ai.model', 'openai/gpt-4.1-mini'),
        ]);

        if ($analysis['success'] ?? false) {
            $this->completeScan($scan, $analysis);

            return [
                'success' => true,
                'duplicate' => false,
                'scan' => $scan->fresh(),
            ];
        }

        $this->failScan($scan, $analysis['error'] ?? __(PlantScannerTranslationKey::AiAnalysisFailed->value));

        return [
            'success' => true,
            'duplicate' => false,
            'scan' => $scan->fresh(),
        ];
    }

    public function getScan(int $scanId, int $userId): ?ScannedPlant
    {
        return ScannedPlant::where('user_id', $userId)
            ->where('id', $scanId)
            ->first();
    }

    public function getUserScans(int $userId, int $perPage = 20)
    {
        return ScannedPlant::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    private function checkForDuplicate(int $userId, string $imageHash): ?ScannedPlant
    {
        return ScannedPlant::where('user_id', $userId)
            ->where('image_hash', $imageHash)
            ->where('status', ScanStatus::Completed)
            ->first();
    }

    private function createScan(int $userId, string $aiProvider, string $optimizedPath, string $originalPath, string $imageHash): ScannedPlant
    {
        return ScannedPlant::create([
            'user_id' => $userId,
            'ai_provider' => $aiProvider,
            'optimized_image_path' => $optimizedPath,
            'metadata' => [
                'original_image_path' => $originalPath,
            ],
            'status' => ScanStatus::Processing,
            'image_hash' => $imageHash,
            'processing_started_at' => now(),
            'tags' => [],
            'photos' => [],
        ]);
    }

    private function completeScan(ScannedPlant $scan, array $analysis): void
    {
        $structured = $analysis['structured_data'] ?? [];
        $scan->update([
            'status' => ScanStatus::Completed,
            'ai_analysis' => $analysis['analysis'],
            'ai_provider_used' => $analysis['provider'] ?? $scan->ai_provider,
            'structured_data' => $structured ?: null,
            'tags' => $structured['recommended_tags'] ?? [],
            'photos' => $structured['reference_images'] ?? [],
            'processing_completed_at' => now(),
        ]);
    }

    private function failScan(ScannedPlant $scan, string $errorMessage): void
    {
        $scan->update([
            'status' => ScanStatus::Failed,
            'error_message' => $errorMessage,
            'processing_completed_at' => now(),
        ]);
    }

    private function extendExecutionTime(): void
    {
        if (function_exists('set_time_limit')) {
            $executionTime = config('plantscanner.ai.php_execution_time', 120);
            if ($executionTime > 0) {
                @ini_set('max_execution_time', (string) $executionTime);
                @set_time_limit($executionTime);
            }
        }
    }
}
