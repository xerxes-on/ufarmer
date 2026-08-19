<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\PlantScanner\Enums\PlantScannerTranslationKey;

class ScanPlantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:10240', 'mimes:jpeg,png,jpg,gif,svg'],
            'ai_provider' => ['nullable', 'string'],
            'lang' => ['nullable', 'string', 'in:en,ru,uz'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => __(PlantScannerTranslationKey::ValidationImageRequired->value),
            'image.image' => __(PlantScannerTranslationKey::ValidationImageInvalid->value),
            'image.max' => __(PlantScannerTranslationKey::ValidationImageMaxSize->value),
            'image.mimes' => __(PlantScannerTranslationKey::ValidationImageInvalidFormat->value),
            'lang.in' => __(PlantScannerTranslationKey::ValidationLangInvalid->value),
        ];
    }
}
