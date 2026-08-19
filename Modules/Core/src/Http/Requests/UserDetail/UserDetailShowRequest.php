<?php

declare(strict_types=1);

namespace Modules\Core\Http\Requests\UserDetail;

use Illuminate\Foundation\Http\FormRequest;

class UserDetailShowRequest extends FormRequest
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
