<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\AreaCrop;

use Illuminate\Foundation\Http\FormRequest;

class AreaCropStoreRequest extends FormRequest
{
    public const FIELD_CROP_UUID = 'crop_uuid';

    public const FIELD_CROP_ID = 'crop_id';

    public const FIELD_AREA = 'area';

    public const FIELD_DATE_STARTED = 'date_started';

    public const FIELD_EXPECTED_HARVEST_DATE = 'expected_harvest_date';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_CROP_UUID => ['required_without:'.self::FIELD_CROP_ID, 'uuid', 'exists:crops,uuid'],
            self::FIELD_CROP_ID => ['required_without:'.self::FIELD_CROP_UUID, 'integer', 'exists:crops,id'],
            self::FIELD_AREA => ['nullable', 'numeric', 'min:0'],
            self::FIELD_DATE_STARTED => ['nullable', 'date'],
            self::FIELD_EXPECTED_HARVEST_DATE => ['nullable', 'date', 'after:'.self::FIELD_DATE_STARTED],
        ];
    }

    public function cropUuid(): ?string
    {
        return $this->input(self::FIELD_CROP_UUID);
    }

    public function cropId(): ?int
    {
        return $this->filled(self::FIELD_CROP_ID) ? (int) $this->input(self::FIELD_CROP_ID) : null;
    }

    public function area(?float $fallback = null): ?float
    {
        return $this->filled(self::FIELD_AREA)
            ? (float) $this->input(self::FIELD_AREA)
            : $fallback;
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
