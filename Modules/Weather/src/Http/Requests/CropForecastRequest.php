<?php

declare(strict_types=1);

namespace Modules\Weather\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Weather\Enums\WeatherTranslationKey;

class CropForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crop_id' => ['required', 'integer', 'exists:crops,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'crop_id.required' => __(WeatherTranslationKey::ValidationCropUuidRequired->value),
            'crop_id.exists' => __(WeatherTranslationKey::ValidationCropUuidExists->value),
            'latitude.required' => __(WeatherTranslationKey::ValidationLatitudeRequired->value),
            'latitude.numeric' => __(WeatherTranslationKey::ValidationLatitudeNumeric->value),
            'latitude.between' => __(WeatherTranslationKey::ValidationLatitudeBetween->value),
            'longitude.required' => __(WeatherTranslationKey::ValidationLongitudeRequired->value),
            'longitude.numeric' => __(WeatherTranslationKey::ValidationLongitudeNumeric->value),
            'longitude.between' => __(WeatherTranslationKey::ValidationLongitudeBetween->value),
        ];
    }

    public function getCropId(): string
    {
        return $this->input('crop_id');
    }

    public function getLatitude(): float
    {
        return (float) $this->input('latitude');
    }

    public function getLongitude(): float
    {
        return (float) $this->input('longitude');
    }
}
