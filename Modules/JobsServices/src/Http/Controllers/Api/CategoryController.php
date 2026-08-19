<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\JobsServices\Http\Resources\CategoryResource;
use Modules\JobsServices\Models\JobCategory;
use Modules\JobsServices\Models\ServiceCategory;

class CategoryController extends Controller
{
    public function jobCategories()
    {
        $categories = JobCategory::active()
            ->ordered()
            ->withCount('jobs')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function serviceCategories()
    {
        $categories = ServiceCategory::active()
            ->ordered()
            ->withCount('services')
            ->get();

        return CategoryResource::collection($categories);
    }
}
