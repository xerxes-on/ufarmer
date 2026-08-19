<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Exceptions\CoreException;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Core\Models\UserDetail;

final class UserDetailService
{
    public function __construct(
        private readonly UserImageService $imageService
    ) {}

    public function getUserDetails(User $user): array
    {
        $detail = $user->detail()->with(['region', 'city'])->first();

        return [
            'user_details' => $detail,
            'name' => $user->name,
            'phone' => $user->phone,
        ];
    }

    public function createUserDetails(User $user, string $name, int $regionId, int $cityId): array
    {
        if ($user->detail()->exists()) {
            throw new CoreException(CoreTranslationKey::USER_DETAIL_ALREADY_EXISTS->value, 409);
        }

        $this->assertCityBelongsToRegion($cityId, $regionId);

        $detail = DB::transaction(function () use ($user, $name, $regionId, $cityId) {
            $user->update(['name' => $name]);

            return $user->detail()->create([
                'region_id' => $regionId,
                'city_id' => $cityId,
            ]);
        });

        $detail->load(['region', 'city']);

        return [
            'user_details' => $detail,
            'name' => $user->name,
            'phone' => $user->phone,
        ];
    }

    public function updateUserDetails(
        User $user,
        UserDetail $userDetail,
        array $attributes,
        ?UploadedFile $image
    ): array {
        if (isset($attributes['region_id'], $attributes['city_id'])) {
            $this->assertCityBelongsToRegion((int) $attributes['city_id'], (int) $attributes['region_id']);
        }

        DB::transaction(function () use ($user, $userDetail, $attributes, $image): void {
            $userUpdates = [];
            if (array_key_exists('name', $attributes)) {
                $userUpdates['name'] = $attributes['name'];
            }

            if (array_key_exists('phone', $attributes)) {
                $userUpdates['phone'] = $attributes['phone'];
            }

            if ($userUpdates !== []) {
                $user->update($userUpdates);
            }

            $detailUpdates = array_filter($attributes, static fn ($key) => in_array($key, [
                'region_id',
                'city_id',
                'address',
                'latitude',
                'longitude',
            ], true), ARRAY_FILTER_USE_KEY);

            if ($image instanceof UploadedFile) {
                if ($userDetail->image) {
                    $this->imageService->deleteImageFile($userDetail->image);
                }

                $detailUpdates['image'] = $this->imageService->storeImage($image);
            }

            if ($detailUpdates !== []) {
                $userDetail->update($detailUpdates);
            }
        });

        $userDetail->load(['region', 'city']);

        return [
            'user_details' => $userDetail,
            'name' => $user->name,
            'phone' => $user->phone,
        ];
    }

    public function deleteUserDetails(UserDetail $userDetail): void
    {
        DB::transaction(function () use ($userDetail): void {
            if ($userDetail->image) {
                $this->imageService->deleteImageFile($userDetail->image);
            }

            $userDetail->delete();
        });
    }

    public function deleteImage(UserDetail $userDetail): void
    {
        $this->imageService->deleteImage($userDetail);
    }

    public function getOrCreateDetail(User $user): UserDetail
    {
        return $user->detail()->firstOrCreate([], []);
    }

    public function ensureUserDetailExists(User $user): UserDetail
    {
        $detail = $user->detail()->first();

        if (! $detail) {
            throw new CoreException(CoreTranslationKey::USER_DETAIL_NOT_FOUND->value, 404);
        }

        return $detail;
    }

    private function assertCityBelongsToRegion(int $cityId, int $regionId): void
    {
        $exists = City::where('id', $cityId)
            ->where('region_id', $regionId)
            ->exists();

        if (! $exists) {
            throw new CoreException(CoreTranslationKey::USER_DETAIL_CITY_MISMATCH->value, 422);
        }
    }
}
