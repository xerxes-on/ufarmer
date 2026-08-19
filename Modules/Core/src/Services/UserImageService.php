<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Exceptions\CoreException;
use Modules\Core\Models\UserDetail;

final class UserImageService
{
    private const STORAGE_DISK = 'public';

    private const IMAGE_DIRECTORY = 'user-images';

    public function uploadImage(UserDetail $userDetail, UploadedFile $image): string
    {
        if ($userDetail->image) {
            $this->deleteImageFile($userDetail->image);
        }

        $path = $image->store(self::IMAGE_DIRECTORY, self::STORAGE_DISK);
        $userDetail->update(['image' => $path]);

        return $path;
    }

    public function deleteImage(UserDetail $userDetail): void
    {
        if (! $userDetail->image) {
            throw new CoreException(CoreTranslationKey::USER_DETAIL_NO_IMAGE->value, 404);
        }

        $this->deleteImageFile($userDetail->image);
        $userDetail->update(['image' => null]);
    }

    public function deleteImageFile(string $path): void
    {
        Storage::disk(self::STORAGE_DISK)->delete($path);
    }

    public function storeImage(UploadedFile $image): string
    {
        return $image->store(self::IMAGE_DIRECTORY, self::STORAGE_DISK);
    }

    public function getStorageDisk(): string
    {
        return self::STORAGE_DISK;
    }

    public function getImageDirectory(): string
    {
        return self::IMAGE_DIRECTORY;
    }
}
