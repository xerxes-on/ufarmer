<?php

declare(strict_types=1);

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\General\Http\Requests\Story\StoryIndexRequest;
use Modules\General\Services\StoryService;
use Modules\General\Transformers\StoryResource;

final class StoryController extends Controller
{
    public function __construct(private readonly StoryService $storyService) {}

    public function index(StoryIndexRequest $request): JsonResponse
    {
        $stories = $this->storyService->paginateStories(
            $request->includeInactive(),
            $request->perPage()
        );

        return $this->respondWithResource(StoryResource::collection($stories));
    }
}
