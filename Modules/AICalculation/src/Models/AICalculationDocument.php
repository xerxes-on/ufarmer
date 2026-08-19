<?php

declare(strict_types=1);

namespace Modules\AICalculation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

abstract class AICalculationDocument extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AICalculationRequest::class, 'ai_calculation_request_id');
    }

    public function getFileSizeFormattedAttribute(): ?string
    {
        $bytes = $this->getAttribute('file_size');

        if ($bytes === null) {
            return null;
        }

        return number_format(((int) $bytes) / 1024, 1).' KB';
    }

    public function getUrlAttribute(): ?string
    {
        $publicUrl = $this->getAttribute('public_url');

        if ($publicUrl) {
            return (string) $publicUrl;
        }

        $disk = $this->getAttribute('disk');
        $path = $this->getAttribute('path');

        if (! $disk || ! $path) {
            return null;
        }

        return Storage::disk((string) $disk)->url((string) $path);
    }
}
