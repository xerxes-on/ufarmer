<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\JobsServices\Models\ServiceCategory;

class UpdateServiceCategoryRequest extends FormRequest
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
            'remove_icon_image' => ['boolean'],
            'remove_category_image' => ['boolean'],
            'remove_banner_image' => ['boolean'],
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

    public function icon(): ?string
    {
        return $this->input('icon');
    }

    public function appliesTo(): string
    {
        return $this->input('applies_to');
    }

    public function sortOrder(): ?int
    {
        return $this->filled('sort_order') ? (int) $this->input('sort_order') : null;
    }

    public function isActive(): ?bool
    {
        return $this->has('is_active') ? (bool) $this->input('is_active') : null;
    }

    public function shouldRemoveIconImage(): bool
    {
        return (bool) $this->input('remove_icon_image', false);
    }

    public function shouldRemoveCategoryImage(): bool
    {
        return (bool) $this->input('remove_category_image', false);
    }

    public function shouldRemoveBannerImage(): bool
    {
        return (bool) $this->input('remove_banner_image', false);
    }

    public function category(): ServiceCategory
    {
        return $this->route('category');
    }
}
