<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\PaidService\PaidServiceDestroyRequest;
use Modules\Core\Http\Requests\PaidService\PaidServiceIndexRequest;
use Modules\Core\Http\Requests\PaidService\PaidServiceShowRequest;
use Modules\Core\Http\Requests\PaidService\PaidServiceStoreRequest;
use Modules\Core\Http\Requests\PaidService\PaidServiceUpdateRequest;
use Modules\Core\Http\Resources\PaidServiceResource;
use Modules\Core\Models\PaidService;

final class PaidServiceController extends Controller
{
    public function index(PaidServiceIndexRequest $request): JsonResponse
    {
        $query = PaidService::query();

        if ($request->hasStatus()) {
            $query->where('status', $request->status());
        }

        if ($request->hasPaidFilter()) {
            $request->isPaid()
                ? $query->paid()
                : $query->free();
        }

        $services = $query->orderBy('created_at', 'desc')
            ->paginate($request->perPage());

        return $this->respondSuccess(
            PaidServiceResource::collection($services),
            null,
            200,
            $this->paginationMeta($services)
        );
    }

    public function store(PaidServiceStoreRequest $request): JsonResponse
    {
        $service = PaidService::create([
            'slug' => $request->slug(),
            'name' => $request->nameTranslations(),
            'description' => $request->descriptionTranslations(),
            'is_paid' => $request->isPaid(),
            'price' => $request->price(),
            'currency' => $request->currency(),
            'applicable_roles' => $request->applicableRoles(),
            'config' => $request->serviceConfig(),
            'valid_from' => $request->validFrom(),
            'valid_until' => $request->validUntil(),
            'status' => $request->status(),
        ]);

        return $this->respondSuccess(
            new PaidServiceResource($service),
            __('paid_service.created'),
            201
        );
    }

    public function show(PaidServiceShowRequest $request, PaidService $paidService): JsonResponse
    {
        return $this->respondSuccess(new PaidServiceResource($paidService));
    }

    public function update(PaidServiceUpdateRequest $request, PaidService $paidService): JsonResponse
    {
        $data = array_filter([
            'slug' => $request->slug(),
            'name' => $request->nameTranslations(),
            'description' => $request->descriptionTranslations(),
            'is_paid' => $request->isPaid(),
            'price' => $request->price(),
            'currency' => $request->currency(),
            'applicable_roles' => $request->applicableRoles(),
            'config' => $request->serviceConfig(),
            'valid_from' => $request->validFrom(),
            'valid_until' => $request->validUntil(),
            'status' => $request->status(),
        ], fn ($value) => $value !== null);

        $paidService->update($data);

        return $this->respondSuccess(
            new PaidServiceResource($paidService->fresh()),
            __('paid_service.updated')
        );
    }

    public function destroy(PaidServiceDestroyRequest $request, PaidService $paidService): JsonResponse
    {
        $paidService->delete();

        return $this->respondSuccess(null, __('paid_service.deleted'));
    }

    public function restore(PaidService $paidService): JsonResponse
    {
        $paidService->restore();

        return $this->respondSuccess(
            new PaidServiceResource($paidService),
            __('paid_service.restored')
        );
    }

    public function toggleStatus(PaidService $paidService): JsonResponse
    {
        $newStatus = $paidService->status->value === 'active' ? 'inactive' : 'active';
        $paidService->update(['status' => $newStatus]);

        return $this->respondSuccess(
            new PaidServiceResource($paidService->fresh()),
            __('paid_service.status_updated')
        );
    }
}
