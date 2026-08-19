<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\JobsServices\Models\Service;
use Modules\JobsServices\Models\ServiceMedia;

final class ServiceService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function createService(array $data, int $userId, array $images = []): Service
    {
        $data['user_id'] = $userId;

        return DB::transaction(function () use ($data, $images): Service {
            $service = Service::create($data);

            $this->handleImageUploads($service, $images);

            return $service->load(['category', 'user', 'media']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateService(Service $service, array $data, int $userId): Service
    {
        $this->ensureOwnership($service, $userId);

        $service->update($data);

        return $service->load(['category', 'user', 'media']);
    }

    public function deleteService(Service $service, int $userId): void
    {
        $this->ensureOwnership($service, $userId);

        $service->update(['status' => 'deleted']);
    }

    public function toggleStatus(Service $service, int $userId): Service
    {
        $this->ensureOwnership($service, $userId);

        $newStatus = $service->status === 'open' ? 'closed' : 'open';
        $service->update(['status' => $newStatus]);

        return $service->load(['category', 'user', 'media']);
    }

    private function ensureOwnership(Service $service, int $userId): void
    {
        if ($service->user_id !== $userId) {
            throw new AuthorizationException;
        }
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function handleImageUploads(Service $service, array $images): void
    {
        foreach ($images as $index => $file) {
            $path = $file->store('services/'.$service->id, 'public');

            ServiceMedia::create([
                'service_id' => $service->id,
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
