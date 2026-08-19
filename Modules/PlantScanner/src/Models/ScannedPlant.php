<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;
use Modules\PlantScanner\Enums\ScanStatus;

class ScannedPlant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => ScanStatus::class,
        'external_api_response' => 'array',
        'metadata' => 'array',
        'structured_data' => 'array',
        'tags' => 'array',
        'photos' => 'array',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'plant_id_raw_response' => 'array',
        'insect_id_raw_response' => 'array',
        'health_assessment_raw_response' => 'array',
        'ai_enriched_data' => 'array',
        'ai_questions' => 'array',
        'farmer_advice' => 'array',
        'agent_analysis' => 'array',
        'ai_usage_details' => 'array',
        'ai_cost_usd' => 'decimal:6',
        'similarity_score' => 'decimal:2',
        'is_insect' => 'boolean',
        'insect_probability' => 'decimal:2',
        'is_healthy' => 'boolean',
        'health_probability' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plantDetail(): BelongsTo
    {
        return $this->belongsTo(PlantDetail::class);
    }

    public function pestDetail(): BelongsTo
    {
        return $this->belongsTo(PestDetail::class);
    }

    /**
     * @return array{en: ?string, ru: ?string, uz: ?string}
     */
    public function resultNames(): array
    {
        $names = ['en' => null, 'ru' => null, 'uz' => null];

        if ($this->scan_mode === 'recognition') {
            $names = $this->mergeLocalizedNames($names, [
                'en' => $this->plantDetail?->common_name_en,
                'ru' => $this->plantDetail?->common_name_ru,
                'uz' => $this->plantDetail?->common_name_uz,
            ]);
            $names = $this->mergeLocalizedNames($names, $this->ai_enriched_data['plant_name'] ?? null);
        } elseif ($this->scan_mode === 'pests') {
            $names = $this->mergeLocalizedNames($names, [
                'en' => $this->pestDetail?->common_name_en,
                'ru' => $this->pestDetail?->common_name_ru,
                'uz' => $this->pestDetail?->common_name_uz,
            ]);
            $names = $this->mergeLocalizedNames($names, data_get($this->ai_enriched_data, 'other_names.0'));

            if (blank($names['en']) && filled($this->identified_insect_name)) {
                $names['en'] = $this->identified_insect_name;
            }
        } elseif ($this->scan_mode === 'diagnosis') {
            $names = $this->mergeLocalizedNames($names, data_get($this->ai_enriched_data, 'disease_name'));

            if (blank($names['en']) && filled($this->identified_disease_name)) {
                $names['en'] = $this->identified_disease_name;
            }
        }

        return $names;
    }

    public function resultScientificName(): ?string
    {
        $name = match ($this->scan_mode) {
            'recognition' => $this->plantDetail?->scientific_name
                ?? data_get($this->ai_enriched_data, 'scientific_name'),
            'pests' => $this->pestDetail?->scientific_name
                ?? data_get($this->insect_id_raw_response, 'result.classification.suggestions.0.details.scientific_name'),
            default => null,
        };

        return is_string($name) && filled(trim($name)) ? trim($name) : null;
    }

    public function uploadedImageUrl(): ?string
    {
        if (blank($this->optimized_image_path)) {
            return null;
        }

        $disk = (string) (config('plantscanner.image.optimized_disk')
            ?: config('filesystems.default', 'public'));

        return $this->absoluteUrl(
            Storage::disk($disk)
                ->url($this->optimized_image_path)
        );
    }

    /**
     * @return array<int, string>
     */
    public function referenceImageUrls(): array
    {
        $images = match ($this->scan_mode) {
            'recognition' => array_merge(
                [$this->plantDetail?->image_url],
                $this->imageList($this->plantDetail?->gallery_images),
                $this->imageList($this->metadata['unsplash_images'] ?? null),
            ),
            'pests' => array_merge(
                [$this->pestDetail?->image_url],
                $this->imageList($this->pestDetail?->gallery_images),
                $this->imageList($this->metadata['pest_images'] ?? null),
            ),
            'diagnosis' => $this->imageList($this->metadata['disease_images'] ?? null),
            default => $this->imageList($this->photos),
        };

        $urls = [];
        foreach ($images as $image) {
            $candidate = is_array($image) ? ($image['url'] ?? $image['image'] ?? null) : $image;
            if (! is_string($candidate)) {
                continue;
            }

            $candidate = $this->absoluteUrl($candidate);
            if ($candidate !== null && ! in_array($candidate, $urls, true)) {
                $urls[] = $candidate;
            }
        }

        return $urls;
    }

    /** @return array<int, mixed> */
    private function imageList(mixed $images): array
    {
        return is_array($images) ? array_values($images) : [];
    }

    /**
     * @param  array{en: ?string, ru: ?string, uz: ?string}  $names
     * @return array{en: ?string, ru: ?string, uz: ?string}
     */
    private function mergeLocalizedNames(array $names, mixed $candidate): array
    {
        if (! is_array($candidate)) {
            return $names;
        }

        foreach (['en', 'ru', 'uz'] as $locale) {
            $value = $candidate[$locale] ?? null;
            if (blank($names[$locale]) && is_string($value) && filled(trim($value))) {
                $names[$locale] = trim($value);
            }
        }

        return $names;
    }

    private function absoluteUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    public function isPending(): bool
    {
        return $this->status === ScanStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === ScanStatus::Processing;
    }

    public function isCompleted(): bool
    {
        return $this->status === ScanStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === ScanStatus::Failed;
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => ScanStatus::Completed]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => ScanStatus::Failed]);
    }
}
