<?php

declare(strict_types=1);

namespace Modules\JobsServices\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\JobsServices\Http\Requests\StoreJobApplicationRequest;
use Modules\JobsServices\Http\Resources\JobApplicationResource;
use Modules\JobsServices\Models\Job;
use Modules\JobsServices\Models\JobApplication;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = JobApplication::with(['job', 'applicant']);

        // Filter by job
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get applications for jobs owned by current user
        if ($request->has('my_jobs')) {
            $jobIds = Job::where('user_id', auth()->id())->pluck('id');
            $query->whereIn('job_id', $jobIds);
        }

        // Get applications made by current user
        if ($request->has('my_applications')) {
            $query->where('applicant_id', auth()->id());
        }

        $applications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return JobApplicationResource::collection($applications);
    }

    public function store(StoreJobApplicationRequest $request)
    {
        $job = Job::findOrFail($request->job_id);

        // Check if job is open
        if ($job->status !== 'open') {
            return $this->respondError('Job is not open for applications', 400);
        }

        // Check if user is not the job owner
        if ($job->user_id === auth()->id()) {
            return $this->respondError('Cannot apply to your own job', 400);
        }

        // Check if user already applied
        $existingApplication = JobApplication::where('job_id', $job->id)
            ->where('applicant_id', auth()->id())
            ->first();

        if ($existingApplication) {
            return $this->respondError('You have already applied to this job', 400);
        }

        $data = $request->validated();
        $data['applicant_id'] = auth()->id();

        $application = JobApplication::create($data);

        // Increment applications count
        $job->increment('applications_count');

        return new JobApplicationResource($application->load(['job', 'applicant']));
    }

    public function show(JobApplication $application)
    {
        // Check if user is the applicant or job owner
        if (! in_array(auth()->id(), [$application->applicant_id, $application->job->user_id])) {
            return $this->respondError('Unauthorized', 403);
        }

        return new JobApplicationResource($application->load(['job', 'applicant']));
    }

    public function accept(JobApplication $application)
    {
        // Check if user is the job owner
        if ($application->job->user_id !== auth()->id()) {
            return $this->respondError('Unauthorized', 403);
        }

        if ($application->status !== 'pending') {
            return $this->respondError('Application is not pending', 400);
        }

        $application->accept();

        return new JobApplicationResource($application->load(['job', 'applicant']));
    }

    public function reject(JobApplication $application, Request $request)
    {
        // Check if user is the job owner
        if ($application->job->user_id !== auth()->id()) {
            return $this->respondError('Unauthorized', 403);
        }

        if ($application->status !== 'pending') {
            return $this->respondError('Application is not pending', 400);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $application->reject($request->reason);

        return new JobApplicationResource($application->load(['job', 'applicant']));
    }

    public function withdraw(JobApplication $application)
    {
        // Check if user is the applicant
        if ($application->applicant_id !== auth()->id()) {
            return $this->respondError('Unauthorized', 403);
        }

        if ($application->status !== 'pending') {
            return $this->respondError('Cannot withdraw non-pending application', 400);
        }

        $application->withdraw();

        return new JobApplicationResource($application->load(['job', 'applicant']));
    }

    public function myApplications(Request $request)
    {
        $query = JobApplication::with(['job.category', 'job.user', 'job.media'])
            ->where('applicant_id', auth()->id());

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return JobApplicationResource::collection($applications);
    }
}
