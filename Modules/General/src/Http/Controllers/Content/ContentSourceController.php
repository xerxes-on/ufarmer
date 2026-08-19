<?php

declare(strict_types=1);

namespace Modules\General\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\General\Models\ContentSource;
use Modules\General\Transformers\Content\ContentSourceResource;

class ContentSourceController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = ContentSource::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->respondWithResource(ContentSourceResource::collection($sources));
    }
}
