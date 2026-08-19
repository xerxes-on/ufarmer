<?php

declare(strict_types=1);

namespace Modules\General\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\General\Http\Requests\Content\StoreContentDraftRequest;
use Modules\General\Models\ContentDraft;
use Modules\General\Transformers\Content\ContentDraftResource;

class ContentDraftController extends Controller
{
    public function store(StoreContentDraftRequest $request): JsonResponse
    {
        $draft = ContentDraft::create($request->validated() + [
            'status' => ContentDraft::STATUS_DRAFT,
        ]);

        return $this->respondWithResource(new ContentDraftResource($draft), 201);
    }
}
