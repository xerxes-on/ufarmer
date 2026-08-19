<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\Crop;

use Illuminate\Foundation\Http\FormRequest;

class SyncCropsRequest extends FormRequest
{
    public const FIELD_CROP_IDS = 'crop_ids';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_CROP_IDS => ['required', 'array'],
            self::FIELD_CROP_IDS.'.*' => ['required', 'integer', 'exists:crops,id'],
        ];
    }

    /**
     * @return list<string>
     */
    public function cropIds(): array
    {
        return $this->input(self::FIELD_CROP_IDS, []);
    }
}
