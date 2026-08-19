<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\Crop;

use Illuminate\Foundation\Http\FormRequest;

class AttachCropRequest extends FormRequest
{
    public const FIELD_CROP_ID = 'crop_id';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_CROP_ID => ['required', 'integer', 'exists:crops,id'],
        ];
    }

    public function cropId(): string
    {
        return $this->string(self::FIELD_CROP_ID)->toString();
    }
}
