<?php

declare(strict_types=1);

namespace Modules\General\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class ArticleForFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'crop_ids' => ['nullable', 'array'],
            'crop_ids.*' => ['integer', 'exists:crops,id'],
        ];
    }

    public function limit(): int
    {
        return (int) $this->input('limit', 5);
    }

    public function requestedCropIds(): array
    {
        return $this->input('crop_ids', []);
    }
}
