<?php

declare(strict_types=1);

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\General\Http\Requests\Article\ArticleForFarmerRequest;
use Modules\General\Http\Requests\Article\ArticleIndexRequest;
use Modules\General\Services\ArticleService;
use Modules\General\Transformers\ArticleResource;
use Xerxes\AuthBridge\Models\Farmer;

final class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $articleService) {}

    public function forFarmer(ArticleForFarmerRequest $request): JsonResponse
    {
        /** @var Farmer|null $farmer */
        $farmer = $request->user();

        $articles = $this->articleService->getArticlesForFarmer(
            $farmer,
            $request->limit(),
            $request->requestedCropIds()
        );

        return $this->respondWithResource(ArticleResource::collection($articles));
    }

    public function index(ArticleIndexRequest $request): JsonResponse
    {
        $articles = $this->articleService->listArticles(
            $request->cropIds(),
            $request->tag()
        );

        return $this->respondWithResource(ArticleResource::collection($articles));
    }
}
