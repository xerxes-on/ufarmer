<?php

declare(strict_types=1);

namespace Modules\General\Http\Requests\ProductStat;

use Illuminate\Foundation\Http\FormRequest;

class CropStatsRequest extends FormRequest
{
    public const ROUTE_PARAM_CROP = 'id';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function cropId(): int
    {
        return (int) $this->route(self::ROUTE_PARAM_CROP);
    }
}
