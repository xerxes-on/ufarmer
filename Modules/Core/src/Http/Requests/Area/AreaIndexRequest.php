<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class AreaIndexRequest extends FormRequest
{
    public const PARAM_PER_PAGE = 'per_page';

    public const PARAM_INCLUDE_INACTIVE = 'include_inactive';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::PARAM_PER_PAGE => ['nullable', 'integer', 'min:1'],
            self::PARAM_INCLUDE_INACTIVE => ['nullable', 'boolean'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input(self::PARAM_PER_PAGE, 15);
    }

    public function includeInactive(): bool
    {
        return $this->boolean(self::PARAM_INCLUDE_INACTIVE);
    }
}
