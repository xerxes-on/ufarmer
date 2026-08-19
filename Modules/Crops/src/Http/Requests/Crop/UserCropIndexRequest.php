<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\Crop;

use Illuminate\Foundation\Http\FormRequest;

class UserCropIndexRequest extends FormRequest
{
    private const PARAM_PER_PAGE = 'per_page';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::PARAM_PER_PAGE => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input(self::PARAM_PER_PAGE, 15);
    }
}
