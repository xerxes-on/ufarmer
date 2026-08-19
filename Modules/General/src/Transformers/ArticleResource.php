<?php

declare(strict_types=1);

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'uz');
        if (! in_array($locale, ['uz', 'ru', 'oz'])) {
            $locale = 'uz';
        }

        return [
            'id' => $this->id,
            'title' => [
                'uz' => $this->title_uz,
                'ru' => $this->title_ru,
                'oz' => $this->title_oz,
                'current' => $this->{"title_{$locale}"},
            ],
            'body' => [
                'uz' => $this->body_uz,
                'ru' => $this->body_ru,
                'oz' => $this->body_oz,
                'current' => $this->{"body_{$locale}"},
            ],
            'preview' => [
                'uz' => $this->preview_uz,
                'ru' => $this->preview_ru,
                'oz' => $this->preview_oz,
                'current' => $this->{"preview_{$locale}"},
            ],
            'attachment' => $this->attachment,
            'icon' => $this->icon,
            'preview_image' => $this->preview_image,
            'crop_ids' => $this->crop_ids,
            'crops' => ($this->crops ?? collect())->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name ?? ($c->title ?? null),
                ];
            }),
            'tags' => $this->whenLoaded('tags', function () use ($locale) {
                return $this->tags->map(function ($tag) use ($locale) {
                    $current = $tag->{"name_{$locale}"} ?? null;
                    if (! $current) {
                        $current = $tag->name_oz ?? $tag->name_ru ?? $tag->name_uz ?? $tag->slug;
                    }

                    return [
                        'slug' => $tag->slug,
                        'name' => [
                            'uz' => $tag->name_uz,
                            'ru' => $tag->name_ru,
                            'oz' => $tag->name_oz,
                            'current' => $current,
                        ],
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
