<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\AreaCrop;

use Illuminate\Foundation\Http\FormRequest;

class AreaCropToggleRequest extends FormRequest
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
