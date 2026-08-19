<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\AreaCrop;

use Illuminate\Foundation\Http\FormRequest;

class AreaCropUpdateRequest extends FormRequest
{
    public const FIELD_AREA = AreaCropStoreRequest::FIELD_AREA;

    public const FIELD_DATE_STARTED = AreaCropStoreRequest::FIELD_DATE_STARTED;

    public const FIELD_EXPECTED_HARVEST_DATE = AreaCropStoreRequest::FIELD_EXPECTED_HARVEST_DATE;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_AREA => ['sometimes', 'required', 'numeric', 'min:0'],
            self::FIELD_DATE_STARTED => ['nullable', 'date'],
            self::FIELD_EXPECTED_HARVEST_DATE => ['nullable', 'date', 'after:'.self::FIELD_DATE_STARTED],
        ];
    }

    public function area(): ?float
    {
        return $this->filled(self::FIELD_AREA)
            ? (float) $this->input(self::FIELD_AREA)
            : null;
    }

    public function dateStarted(): ?string
    {
        return $this->input(self::FIELD_DATE_STARTED);
    }

    public function expectedHarvestDate(): ?string
    {
        return $this->input(self::FIELD_EXPECTED_HARVEST_DATE);
    }
}
