<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\Crop;

use Illuminate\Foundation\Http\FormRequest;

class CropIndexRequest extends FormRequest
{
    public const PARAM_PER_PAGE = 'per_page';

    public const PARAM_PAGE = 'page';

    public const PARAM_SEARCH = 'search';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::PARAM_PER_PAGE => ['nullable', 'integer', 'min:1'],
            self::PARAM_PAGE => ['nullable', 'integer', 'min:1'],
            self::PARAM_SEARCH => ['nullable', 'string', 'max:255'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input(self::PARAM_PER_PAGE, 15);
    }

    public function page(): int
    {
        return (int) $this->input(self::PARAM_PAGE, 1);
    }

    public function search(): ?string
    {
        return $this->input(self::PARAM_SEARCH);
    }
}
