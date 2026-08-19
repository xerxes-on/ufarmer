<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\Seeder;

use Illuminate\Foundation\Http\FormRequest;

class ClearRegionsRequest extends FormRequest
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
