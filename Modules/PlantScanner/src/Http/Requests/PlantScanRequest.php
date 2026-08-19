<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\PlantScanner\Enums\PlantScannerTranslationKey;

class PlantScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:102400'],
            'ai_provider' => ['sometimes', 'string', 'in:openai,gemini,claude'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => __(PlantScannerTranslationKey::ValidationImageProvide->value),
            'image.image' => __(PlantScannerTranslationKey::ValidationImageMustBe->value),
            'image.max' => __(PlantScannerTranslationKey::ValidationImageMax100Mb->value),
            'ai_provider.in' => __(PlantScannerTranslationKey::ValidationAiProviderInvalid->value),
        ];
    }
}
