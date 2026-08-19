<?php

declare(strict_types=1);

namespace Modules\General\Http\Requests\Article;

use Illuminate\Foundation\Http\FormRequest;

class ArticleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'crop_ids' => ['nullable', 'array'],
            'crop_ids.*' => ['integer', 'exists:crops,id'],
            'tag' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function cropIds(): array
    {
        return $this->input('crop_ids', []);
    }

    public function tag(): ?string
    {
        return $this->input('tag');
    }
}
