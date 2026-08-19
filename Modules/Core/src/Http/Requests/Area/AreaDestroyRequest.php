<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class AreaDestroyRequest extends FormRequest
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
