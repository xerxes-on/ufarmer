<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\Crop;

use Illuminate\Foundation\Http\FormRequest;

class CropAreaAttachRequest extends FormRequest
{
    public const FIELD_ITEMS = 'items';

    public const FIELD_AREA_ID = 'area_id';

    public const FIELD_AREA = 'area';

    public const FIELD_DATE_STARTED = 'date_started';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_ITEMS => ['required', 'array', 'min:1'],
            self::FIELD_ITEMS.'.*.'.self::FIELD_AREA_ID => ['required', 'integer', 'exists:areas,id'],
            self::FIELD_ITEMS.'.*.'.self::FIELD_AREA => ['nullable', 'numeric', 'min:0'],
            self::FIELD_ITEMS.'.*.'.self::FIELD_DATE_STARTED => ['nullable', 'date'],
        ];
    }

    public function items(): array
    {
        return $this->input(self::FIELD_ITEMS, []);
    }
}
