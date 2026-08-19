<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.uz' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:10'],
            'applies_to' => ['required', 'in:offers,announcements,both'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'icon_image' => ['nullable', 'image', 'max:2048'],
            'category_image' => ['nullable', 'image', 'max:5120'],
            'banner_image' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function categoryName(): array
    {
        return [
            'en' => $this->input('name.en'),
            'ru' => $this->input('name.ru'),
            'uz' => $this->input('name.uz'),
        ];
    }

    public function icon(): string
    {
        return $this->input('icon', '🛠');
    }

    public function appliesTo(): string
    {
        return $this->input('applies_to');
    }

    public function sortOrder(): int
    {
        return (int) $this->input('sort_order', 0);
    }

    public function isActive(): bool
    {
        return (bool) $this->input('is_active', true);
    }
}
