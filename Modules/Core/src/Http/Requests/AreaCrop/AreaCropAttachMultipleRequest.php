<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\AreaCrop;

use Illuminate\Foundation\Http\FormRequest;

class AreaCropAttachMultipleRequest extends FormRequest
{
    public const FIELD_ITEMS = 'items';

    public const FIELD_CROP_UUID = AreaCropStoreRequest::FIELD_CROP_UUID;

    public const FIELD_CROP_ID = AreaCropStoreRequest::FIELD_CROP_ID;

    public const FIELD_AREA = AreaCropStoreRequest::FIELD_AREA;

    public const FIELD_DATE_STARTED = AreaCropStoreRequest::FIELD_DATE_STARTED;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_ITEMS => ['required', 'array', 'min:1'],
            self::FIELD_ITEMS.'.*.'.self::FIELD_CROP_UUID => ['required_without:'.self::FIELD_ITEMS.'.*.'.self::FIELD_CROP_ID, 'uuid', 'exists:crops,uuid'],
            self::FIELD_ITEMS.'.*.'.self::FIELD_CROP_ID => ['required_without:'.self::FIELD_ITEMS.'.*.'.self::FIELD_CROP_UUID, 'integer', 'exists:crops,id'],
            self::FIELD_ITEMS.'.*.'.self::FIELD_AREA => ['nullable', 'numeric', 'min:0'],
            self::FIELD_ITEMS.'.*.'.self::FIELD_DATE_STARTED => ['nullable', 'date'],
        ];
    }

    public function items(): array
    {
        return $this->input(self::FIELD_ITEMS, []);
    }
}
