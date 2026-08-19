<?php

declare(strict_types=1);

namespace Modules\General\Http\Requests\ProductStat;

use Illuminate\Foundation\Http\FormRequest;

class ProductStatTodayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
