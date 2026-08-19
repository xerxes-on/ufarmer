<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Region;

use Illuminate\Foundation\Http\FormRequest;

class RegionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'with_cities' => ['nullable', 'boolean'],
        ];
    }

    public function includeCities(): bool
    {
        return $this->boolean('with_cities', true);
    }
}
