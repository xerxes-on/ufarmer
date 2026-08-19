<?php

declare(strict_types=1);

namespace Modules\General\Contracts;

use Illuminate\Support\Collection;
use Xerxes\AuthBridge\Models\Farmer;

interface ArticleServiceInterface
{
    /**
     * @param  array<int>  $requestedCropIds
     */
    public function getArticlesForFarmer(?Farmer $farmer, int $limit, array $requestedCropIds = []): Collection;

    /**
     * @param  array<int>  $cropIds
     */
    public function listArticles(array $cropIds = [], ?string $tag = null): Collection;
}
