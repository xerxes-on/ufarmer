<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\AreaCrop;

use Illuminate\Foundation\Http\FormRequest;

class HarvestAreaCropRequest extends FormRequest
{
    public const FIELD_YIELD_AMOUNT = 'yield_amount';

    public const FIELD_YIELD_UNIT = 'yield_unit';

    public const FIELD_NOTES = 'notes';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::FIELD_YIELD_AMOUNT => ['nullable', 'numeric', 'min:0'],
            self::FIELD_YIELD_UNIT => ['nullable', 'string', 'max:50'],
            self::FIELD_NOTES => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function yieldAmount(): ?float
    {
        return $this->filled(self::FIELD_YIELD_AMOUNT)
            ? (float) $this->input(self::FIELD_YIELD_AMOUNT)
            : null;
    }

    public function yieldUnit(): ?string
    {
        return $this->input(self::FIELD_YIELD_UNIT);
    }

    public function notes(): ?string
    {
        return $this->input(self::FIELD_NOTES);
    }
}
