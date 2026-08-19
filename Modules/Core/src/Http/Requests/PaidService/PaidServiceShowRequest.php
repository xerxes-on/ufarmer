<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\PaidService;

use Illuminate\Foundation\Http\FormRequest;

class PaidServiceShowRequest extends FormRequest
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
