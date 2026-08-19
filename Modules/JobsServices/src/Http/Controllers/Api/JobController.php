<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\JobsServices\Exceptions\JobsServicesException;
use Modules\JobsServices\Http\Requests\NearbyJobsRequest;
use Modules\JobsServices\Http\Requests\StoreJobRequest;
use Modules\JobsServices\Http\Requests\UpdateJobRequest;
use Modules\JobsServices\Http\Resources\JobDetailResource;
use Modules\JobsServices\Http\Resources\JobResource;
use Modules\JobsServices\Models\Job;
use Modules\JobsServices\Services\JobService;

class JobController extends Controller
{
    public function __construct(private readonly JobService $jobService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Job::with(['category', 'user', 'media']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by title or body
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        // Filter by price range
        if ($request->has('price_min')) {
            $query->where(function ($q) use ($request): void {
                $q->where(function ($q2) use ($request): void {
                    $q2->where('price_mode', 'fixed')
                        ->where('price_fixed', '>=', $request->price_min);
                })->orWhere(function ($q2) use ($request): void {
                    $q2->where('price_mode', 'range')
                        ->where('price_max', '>=', $request->price_min);
                });
            });
        }

        if ($request->has('price_max')) {
            $query->where(function ($q) use ($request): void {
                $q->where(function ($q2) use ($request): void {
                    $q2->where('price_mode', 'fixed')
                        ->where('price_fixed', '<=', $request->price_max);
                })->orWhere(function ($q2) use ($request): void {
                    $q2->where('price_mode', 'range')
                        ->where('price_min', '<=', $request->price_max);
                });
            });
        }

        // Location-based search
        if ($request->has('latitude') && $request->has('longitude')) {
            $radius = $request->radius ?? 10; // Default 10km
            $query->nearby($request->latitude, $request->longitude, $radius);
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        if ($sortBy === 'distance' && $request->has('latitude')) {
            // Already sorted by distance in nearby scope
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $jobs = $query->paginate($request->per_page ?? 20);

        return JobResource::collection($jobs);
    }

    public function store(StoreJobRequest $request): JobDetailResource
    {
        $images = $request->hasFile('images') ? $request->file('images') : [];

        $job = $this->jobService->createJob(
            $request->validated(),
            (int) auth()->id(),
            $images
        );

        return new JobDetailResource($job);
    }

    public function show(Job $job): JobDetailResource
    {
        $job->load(['category', 'user', 'media', 'applications.applicant']);

        if (auth()->id() !== $job->user_id) {
            $job->incrementViewsCount();
        }

        return new JobDetailResource($job);
    }

    public function update(UpdateJobRequest $request, Job $job): JobDetailResource|JsonResponse
    {
        try {
            $job = $this->jobService->updateJob(
                $job,
                $request->validated(),
                (int) auth()->id()
            );

            return new JobDetailResource($job);
        } catch (AuthorizationException) {
            return $this->respondError(__('jobs_services::messages.unauthorized'), 403);
        } catch (JobsServicesException $e) {
            return $this->respondError(__($e->translationKey), $e->statusCode);
        }
    }

    public function destroy(Job $job): JsonResponse
    {
        try {
            $this->jobService->closeJob($job, (int) auth()->id());

            return $this->respondSuccess(null, __('jobs_services::messages.job_closed'));
        } catch (AuthorizationException) {
            return $this->respondError(__('jobs_services::messages.unauthorized'), 403);
        } catch (JobsServicesException $e) {
            return $this->respondError(__($e->translationKey), $e->statusCode);
        }
    }

    public function myJobs(Request $request): AnonymousResourceCollection
    {
        $query = Job::query()
            ->with(['category', 'media', 'applications'])
            ->where('user_id', auth()->id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return JobResource::collection($jobs);
    }

    public function nearbyJobs(NearbyJobsRequest $request): AnonymousResourceCollection
    {
        $jobs = Job::query()
            ->with(['category', 'user', 'media'])
            ->open()
            ->nearby($request->latitude(), $request->longitude(), $request->radius())
            ->paginate($request->perPage());

        return JobResource::collection($jobs);
    }

    public function completeJob(Job $job): JobDetailResource|JsonResponse
    {
        try {
            $job = $this->jobService->completeJob($job, (int) auth()->id());

            return new JobDetailResource($job);
        } catch (AuthorizationException) {
            return $this->respondError(__('jobs_services::messages.unauthorized'), 403);
        } catch (JobsServicesException $e) {
            return $this->respondError(__($e->translationKey), $e->statusCode);
        }
    }
}
