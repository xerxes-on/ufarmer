<?php

declare(strict_types=1);

namespace Modules\General\Services;

use Illuminate\Support\Collection;
use Modules\General\Contracts\ArticleServiceInterface;
use Modules\General\Models\Article;
use Xerxes\AuthBridge\Models\Farmer;

final class ArticleService implements ArticleServiceInterface
{
    public function getArticlesForFarmer(?Farmer $farmer, int $limit, array $requestedCropIds = []): Collection
    {
        $targetCropIds = collect($requestedCropIds)
            ->filter()
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($targetCropIds->isEmpty() && $farmer instanceof Farmer) {
            $targetCropIds = $farmer->crops()
                ->pluck('crops.id')
                ->map(static fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        if ($targetCropIds->isNotEmpty()) {
            $matching = Article::query()
                ->forCrops($targetCropIds)
                ->with(['tags'])
                ->latest()
                ->limit($limit)
                ->get();

            if ($matching->count() >= $limit) {
                return $matching->take($limit);
            }

            $fallback = Article::query()
                ->general()
                ->whereNotIn('id', $matching->pluck('id'))
                ->with(['tags'])
                ->latest()
                ->limit($limit - $matching->count())
                ->get();

            return $matching->concat($fallback)->take($limit);
        }

        return Article::query()
            ->with(['tags'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function listArticles(array $cropIds = [], ?string $tag = null): Collection
    {
        $query = Article::query()->with(['tags']);

        if (! empty($cropIds)) {
            $query->forCrops($cropIds);
        }

        if ($tag !== null) {
            $query->whereHas('tags', static function ($relation) use ($tag): void {
                $relation->where('slug', $tag);
            });
        }

        return $query->latest()->get();
    }
}
