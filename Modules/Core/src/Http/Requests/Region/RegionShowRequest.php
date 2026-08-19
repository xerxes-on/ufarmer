<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Region;

use Illuminate\Foundation\Http\FormRequest;

class RegionShowRequest extends FormRequest
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
