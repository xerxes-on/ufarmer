<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\JobsServices\Http\Requests\StoreServiceCategoryRequest;
use Modules\JobsServices\Http\Requests\UpdateServiceCategoryRequest;
use Modules\JobsServices\Http\Requests\UpdateServiceCategorySortOrderRequest;
use Modules\JobsServices\Models\ServiceCategory;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = ServiceCategory::query()
            ->withCount(['serviceOffers', 'jobAnnouncements'])
            ->ordered()
            ->paginate(20);

        return view('jobsservices::admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('jobsservices::admin.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $category = ServiceCategory::create([
            'name' => $request->categoryName(),
            'icon' => $request->icon(),
            'applies_to' => $request->appliesTo(),
            'sort_order' => $request->sortOrder(),
            'is_active' => $request->isActive(),
        ]);

        if ($request->hasFile('icon_image')) {
            $category->addMediaFromRequest('icon_image')
                ->toMediaCollection(ServiceCategory::MEDIA_COLLECTION_ICON);
        }

        if ($request->hasFile('category_image')) {
            $category->addMediaFromRequest('category_image')
                ->toMediaCollection(ServiceCategory::MEDIA_COLLECTION_IMAGE);
        }

        if ($request->hasFile('banner_image')) {
            $category->addMediaFromRequest('banner_image')
                ->toMediaCollection(ServiceCategory::MEDIA_COLLECTION_BANNER);
        }

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Service category created successfully.');
    }

    /**
     * Show the specified resource.
     */
    public function show(ServiceCategory $category): View
    {
        $category->loadCount(['serviceOffers', 'jobAnnouncements']);

        return view('jobsservices::admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceCategory $category): View
    {
        return view('jobsservices::admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $category): RedirectResponse
    {
        $category->update([
            'name' => $request->categoryName(),
            'icon' => $request->icon() ?? $category->icon,
            'applies_to' => $request->appliesTo(),
            'sort_order' => $request->sortOrder() ?? $category->sort_order,
            'is_active' => $request->isActive() ?? $category->is_active,
        ]);

        if ($request->shouldRemoveIconImage()) {
            $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_ICON);
        }

        if ($request->shouldRemoveCategoryImage()) {
            $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_IMAGE);
        }

        if ($request->shouldRemoveBannerImage()) {
            $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_BANNER);
        }

        if ($request->hasFile('icon_image')) {
            $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_ICON);
            $category->addMediaFromRequest('icon_image')
                ->toMediaCollection(ServiceCategory::MEDIA_COLLECTION_ICON);
        }

        if ($request->hasFile('category_image')) {
            $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_IMAGE);
            $category->addMediaFromRequest('category_image')
                ->toMediaCollection(ServiceCategory::MEDIA_COLLECTION_IMAGE);
        }

        if ($request->hasFile('banner_image')) {
            $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_BANNER);
            $category->addMediaFromRequest('banner_image')
                ->toMediaCollection(ServiceCategory::MEDIA_COLLECTION_BANNER);
        }

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Service category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceCategory $category): RedirectResponse
    {
        // Check if category has related records
        if ($category->serviceOffers()->exists() || $category->jobAnnouncements()->exists()) {
            return redirect()
                ->route('admin.service-categories.index')
                ->with('error', 'Cannot delete category with existing services or job announcements.');
        }

        // Clear media collections
        $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_ICON);
        $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_IMAGE);
        $category->clearMediaCollection(ServiceCategory::MEDIA_COLLECTION_BANNER);

        $category->delete();

        return redirect()
            ->route('admin.service-categories.index')
            ->with('success', 'Service category deleted successfully.');
    }

    /**
     * Toggle category active status
     */
    public function toggleActive(ServiceCategory $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $category->is_active,
        ]);
    }

    /**
     * Update category sort order
     */
    public function updateSortOrder(UpdateServiceCategorySortOrderRequest $request): JsonResponse
    {
        foreach ($request->categories() as $item) {
            ServiceCategory::query()
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
