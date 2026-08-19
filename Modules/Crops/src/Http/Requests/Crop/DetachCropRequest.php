<?php

declare(strict_types=1);

namespace Modules\Crops\Http\Requests\Crop;

use Illuminate\Foundation\Http\FormRequest;

class DetachCropRequest extends FormRequest
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
