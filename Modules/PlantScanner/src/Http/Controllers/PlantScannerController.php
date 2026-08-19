<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PlantScanner\Enums\PlantScannerTranslationKey;
use Modules\PlantScanner\Http\Requests\ScanPlantRequest;
use Modules\PlantScanner\Http\Resources\ScannedPlantResource;
use Modules\PlantScanner\Services\PlantScannerService;

final class PlantScannerController extends Controller
{
    public function __construct(
        private readonly PlantScannerService $scannerService
    ) {}

    /**
     * Start a new plant scan and return structured AI analysis.
     */
    public function scan(ScanPlantRequest $request): JsonResponse
    {
        try {
            $result = $this->scannerService->scan(
                $request->file('image'),
                $request->user(),
                $request->input('ai_provider'),
                $request->input('lang')
            );

            if ($result['duplicate']) {
                return $this->respondSuccess(
                    [
                        'duplicate_of' => $result['scan']->id,
                        'original_scan' => ScannedPlantResource::make($result['scan'])->resolve($request),
                    ],
                    __(PlantScannerTranslationKey::ImageAlreadyScanned->value)
                );
            }

            return $this->respondSuccess(
                ScannedPlantResource::make($result['scan'])->resolve($request) + ['is_duplicate' => false],
                null,
                201
            );
        } catch (Exception $e) {
            return $this->respondError(
                __(PlantScannerTranslationKey::ScanFailed->value),
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get scan status and results
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $scan = $this->scannerService->getScan($id, $request->user()->id);

        if (! $scan) {
            return $this->respondError(
                __(PlantScannerTranslationKey::ScanNotFound->value),
                404
            );
        }

        return $this->respondSuccess(ScannedPlantResource::make($scan));
    }

    /**
     * Get all user's scans
     */
    public function index(Request $request): JsonResponse
    {
        $scans = $this->scannerService->getUserScans($request->user()->id);

        return $this->respondSuccess([
            'items' => ScannedPlantResource::collection($scans->items())->resolve($request),
            'meta' => [
                'current_page' => $scans->currentPage(),
                'last_page' => $scans->lastPage(),
                'per_page' => $scans->perPage(),
                'total' => $scans->total(),
            ],
        ]);
    }
}
