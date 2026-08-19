<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\JobsServices\Exceptions\JobsServicesException;
use Modules\JobsServices\Models\Job;
use Modules\JobsServices\Models\JobMedia;

final class JobService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function createJob(array $data, int $userId, array $images = []): Job
    {
        $data['user_id'] = $userId;

        return DB::transaction(function () use ($data, $images): Job {
            $job = Job::create($data);

            $this->handleImageUploads($job, $images);

            return $job->load(['category', 'user', 'media']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateJob(Job $job, array $data, int $userId): Job
    {
        $this->ensureOwnership($job, $userId);
        $this->ensureJobIsEditable($job);

        $job->update($data);

        return $job->load(['category', 'user', 'media']);
    }

    public function closeJob(Job $job, int $userId): void
    {
        $this->ensureOwnership($job, $userId);

        if ($job->status === 'in_progress') {
            throw new JobsServicesException('jobs_services::messages.cannot_delete_in_progress', 400);
        }

        $job->update(['status' => 'closed']);
    }

    public function completeJob(Job $job, int $userId): Job
    {
        if (! in_array($userId, [$job->user_id, $job->executor_id], true)) {
            throw new AuthorizationException;
        }

        if ($job->status !== 'in_progress') {
            throw new JobsServicesException('jobs_services::messages.job_not_in_progress', 400);
        }

        $job->update([
            'status' => 'finished',
            'finished_at' => now(),
        ]);

        return $job->load(['category', 'user', 'executor', 'media']);
    }

    private function ensureOwnership(Job $job, int $userId): void
    {
        if ($job->user_id !== $userId) {
            throw new AuthorizationException;
        }
    }

    private function ensureJobIsEditable(Job $job): void
    {
        if (in_array($job->status, ['in_progress', 'finished'], true)) {
            throw new JobsServicesException('jobs_services::messages.cannot_edit_job_status', 400);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function handleImageUploads(Job $job, array $images): void
    {
        foreach ($images as $index => $file) {
            $path = $file->store('jobs/'.$job->id, 'public');

            JobMedia::create([
                'job_id' => $job->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'type' => 'image',
                'sort_order' => $index,
            ]);
        }
    }
}
