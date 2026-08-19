<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\UserCropPrice;

use Illuminate\Foundation\Http\FormRequest;

class UserCropPriceHistoryRequest extends FormRequest
{
    private const PARAM_PER_PAGE = 'per_page';

    private const PARAM_PAGE = 'page';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::PARAM_PER_PAGE => ['nullable', 'integer', 'min:1', 'max:100'],
            self::PARAM_PAGE => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input(self::PARAM_PER_PAGE, 10);
    }

    public function page(): int
    {
        return (int) $this->input(self::PARAM_PAGE, 1);
    }
}
