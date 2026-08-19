<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceCategorySortOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'exists:service_categories,id'],
            'categories.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, array{id: int, sort_order: int}>
     */
    public function categories(): array
    {
        return $this->input('categories', []);
    }
}
