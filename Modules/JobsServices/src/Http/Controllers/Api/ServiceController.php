<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\JobsServices\Http\Requests\NearbyServicesRequest;
use Modules\JobsServices\Http\Requests\StoreServiceRequest;
use Modules\JobsServices\Http\Requests\UpdateServiceRequest;
use Modules\JobsServices\Http\Resources\ServiceDetailResource;
use Modules\JobsServices\Http\Resources\ServiceResource;
use Modules\JobsServices\Models\Service;
use Modules\JobsServices\Services\ServiceService;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceService $serviceService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Service::query()->with(['category', 'user', 'media']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        if ($request->has('price_unit')) {
            $query->where('price_unit', $request->price_unit);
        }

        if ($request->has('latitude') && $request->has('longitude')) {
            $radius = $request->radius ?? 10;
            $query->nearby($request->latitude, $request->longitude, $radius);
        }

        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }

        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        if ($sortBy !== 'distance' || ! $request->has('latitude')) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $services = $query->paginate($request->per_page ?? 20);

        return ServiceResource::collection($services);
    }

    public function store(StoreServiceRequest $request): ServiceDetailResource
    {
        $images = $request->hasFile('images') ? $request->file('images') : [];

        $service = $this->serviceService->createService(
            $request->validated(),
            (int) auth()->id(),
            $images
        );

        return new ServiceDetailResource($service);
    }

    public function show(Service $service): ServiceDetailResource
    {
        $service->load(['category', 'user', 'media']);

        if (auth()->id() !== $service->user_id) {
            $service->incrementViewsCount();
        }

        return new ServiceDetailResource($service);
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceDetailResource|JsonResponse
    {
        try {
            $service = $this->serviceService->updateService(
                $service,
                $request->validated(),
                (int) auth()->id()
            );

            return new ServiceDetailResource($service);
        } catch (AuthorizationException) {
            return $this->respondError(__('jobs_services::messages.unauthorized'), 403);
        }
    }

    public function destroy(Service $service): JsonResponse
    {
        try {
            $this->serviceService->deleteService($service, (int) auth()->id());

            return $this->respondSuccess(null, __('jobs_services::messages.service_deleted'));
        } catch (AuthorizationException) {
            return $this->respondError(__('jobs_services::messages.unauthorized'), 403);
        }
    }

    public function myServices(Request $request): AnonymousResourceCollection
    {
        $query = Service::query()
            ->with(['category', 'media'])
            ->where('user_id', auth()->id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return ServiceResource::collection($services);
    }

    public function nearbyServices(NearbyServicesRequest $request): AnonymousResourceCollection
    {
        $services = Service::query()
            ->with(['category', 'user', 'media'])
            ->open()
            ->nearby($request->latitude(), $request->longitude(), $request->radius())
            ->paginate($request->perPage());

        return ServiceResource::collection($services);
    }

    public function toggleStatus(Service $service): ServiceDetailResource|JsonResponse
    {
        try {
            $service = $this->serviceService->toggleStatus($service, (int) auth()->id());

            return new ServiceDetailResource($service);
        } catch (AuthorizationException) {
            return $this->respondError(__('jobs_services::messages.unauthorized'), 403);
        }
    }
}
