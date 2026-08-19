<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\AgronomDetailResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Agronom\Filament\Resources\AgronomDetailResource;
use Modules\Core\Models\UserDetail;

class EditAgronomDetail extends EditRecord
{
    protected static string $resource = AgronomDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $userDetail = $this->record->user?->detail;

        if ($userDetail) {
            $data['_region_id'] = $userDetail->region_id;
            $data['_city_id'] = $userDetail->city_id;
            $data['_address'] = $userDetail->address;
            $data['_latitude'] = $userDetail->latitude;
            $data['_longitude'] = $userDetail->longitude;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $userDetail = $this->record->user?->detail;

        if ($userDetail) {
            $formData = $this->data;

            $userDetail->update([
                'region_id' => $formData['_region_id'] ?? $userDetail->region_id,
                'city_id' => $formData['_city_id'] ?? $userDetail->city_id,
                'address' => $formData['_address'] ?? $userDetail->address,
                'latitude' => $formData['_latitude'] ?? $userDetail->latitude,
                'longitude' => $formData['_longitude'] ?? $userDetail->longitude,
            ]);

            $this->handleProfileImageUpload($userDetail, $formData);
        }
    }

    private function handleProfileImageUpload(UserDetail $userDetail, array $formData): void
    {
        $uploadedImage = $formData['_profile_image'] ?? null;

        if ($uploadedImage === null || $uploadedImage === [] || $uploadedImage === '') {
            return;
        }

        $file = is_array($uploadedImage) ? reset($uploadedImage) : $uploadedImage;

        if (! $file) {
            return;
        }

        $fullPath = null;
        $tempDisk = config('livewire.temporary_file_upload.disk', 'public');

        if ($file instanceof TemporaryUploadedFile) {
            $fullPath = $file->getRealPath();
        } elseif (is_string($file)) {
            $fullPath = Storage::disk($tempDisk)->path($file);
        }

        if (! $fullPath || ! file_exists($fullPath)) {
            \Log::warning('Profile image file not found', [
                'file' => $file,
                'fullPath' => $fullPath,
                'tempDisk' => $tempDisk,
            ]);

            return;
        }

        $userDetail->clearMediaCollection(UserDetail::MEDIA_COLLECTION_PROFILE);

        $userDetail->addMedia($fullPath)
            ->toMediaCollection(UserDetail::MEDIA_COLLECTION_PROFILE);

        if (is_string($file)) {
            Storage::disk($tempDisk)->delete($file);
        }
    }
}
