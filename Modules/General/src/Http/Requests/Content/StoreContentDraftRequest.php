<?php

declare(strict_types=1);

namespace Modules\General\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\General\Models\ContentDraft;

class StoreContentDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $articleTypes = [ContentDraft::TYPE_ARTICLE, ContentDraft::TYPE_NEWS];

        return [
            'content_source_id' => ['nullable', 'integer', 'exists:content_sources,id'],
            'content_type' => ['required', 'string', Rule::in([
                ContentDraft::TYPE_ARTICLE,
                ContentDraft::TYPE_NEWS,
                ContentDraft::TYPE_STORY,
                ContentDraft::TYPE_VIDEO,
            ])],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'array'],
            'title.uz' => ['required', 'string', 'max:255'],
            'title.ru' => ['required', 'string', 'max:255'],
            'title.en' => ['required', 'string', 'max:255'],
            'preview' => ['nullable', 'array'],
            'preview.uz' => ['nullable', 'string'],
            'preview.ru' => ['nullable', 'string'],
            'preview.en' => ['nullable', 'string'],
            'body' => [Rule::requiredIf(fn (): bool => in_array($this->string('content_type')->toString(), $articleTypes, true)), 'nullable', 'array'],
            'body.uz' => [Rule::requiredIf(fn (): bool => in_array($this->string('content_type')->toString(), $articleTypes, true)), 'nullable', 'string'],
            'body.ru' => [Rule::requiredIf(fn (): bool => in_array($this->string('content_type')->toString(), $articleTypes, true)), 'nullable', 'string'],
            'body.en' => [Rule::requiredIf(fn (): bool => in_array($this->string('content_type')->toString(), $articleTypes, true)), 'nullable', 'string'],
            'media_original_url' => ['nullable', 'url', 'max:2048'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'thumbnail_url' => ['nullable', 'url', 'max:2048'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:article_tags,id'],
            'crop_ids' => ['nullable', 'array'],
            'crop_ids.*' => ['integer', 'exists:crops,id'],
            'source_payload' => ['nullable', 'array'],
        ];
    }
}
