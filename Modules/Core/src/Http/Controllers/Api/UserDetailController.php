<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Enums\CoreTranslationKey;
use Modules\Core\Exceptions\CoreException;
use Modules\Core\Http\Requests\UserDetail\NearbyUsersRequest;
use Modules\Core\Http\Requests\UserDetail\UserDetailDeleteImageRequest;
use Modules\Core\Http\Requests\UserDetail\UserDetailDestroyRequest;
use Modules\Core\Http\Requests\UserDetail\UserDetailShowRequest;
use Modules\Core\Http\Requests\UserDetail\UserDetailStoreRequest;
use Modules\Core\Http\Requests\UserDetail\UserDetailUpdateRequest;
use Modules\Core\Models\User;
use Modules\Core\Services\NearbyUsersService;
use Modules\Core\Services\UserDetailService;

final class UserDetailController extends Controller
{
    private const string ROUTE_PARAM_USER = 'user';

    public function __construct(
        private readonly UserDetailService $userDetailService,
        private readonly NearbyUsersService $nearbyUsersService
    ) {}

    public function show(UserDetailShowRequest $request): JsonResponse
    {
        $user = $this->resolveUserOrResponse($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $payload = $this->userDetailService->getUserDetails($user);

        return $this->respondSuccess($payload);
    }

    public function store(UserDetailStoreRequest $request): JsonResponse
    {
        $user = $this->resolveUserOrResponse($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $payload = $this->userDetailService->createUserDetails(
                $user,
                $request->name(),
                $request->regionId(),
                $request->cityId()
            );
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess($payload, __(CoreTranslationKey::USER_DETAIL_CREATED->value), 201);
    }

    public function update(UserDetailUpdateRequest $request): JsonResponse
    {
        $user = $this->resolveUserOrResponse($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $userDetail = $this->userDetailService->getOrCreateDetail($user);

        try {
            $attributes = array_merge(
                $request->userAttributes(),
                $request->detailAttributes()
            );

            $payload = $this->userDetailService->updateUserDetails(
                $user,
                $userDetail,
                $attributes,
                $request->image()
            );
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess($payload, __(CoreTranslationKey::USER_DETAIL_UPDATED->value));
    }

    public function destroy(UserDetailDestroyRequest $request): JsonResponse
    {
        $user = $this->resolveUserOrResponse($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $userDetail = $this->userDetailService->ensureUserDetailExists($user);
            $this->userDetailService->deleteUserDetails($userDetail);
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess(null, __(CoreTranslationKey::USER_DETAIL_DELETED->value));
    }

    public function deleteImage(UserDetailDeleteImageRequest $request): JsonResponse
    {
        $user = $this->resolveUserOrResponse($request);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        try {
            $userDetail = $this->userDetailService->ensureUserDetailExists($user);
            $this->userDetailService->deleteImage($userDetail);
        } catch (CoreException $exception) {
            return $this->respondError(__($exception->translationKey), $exception->statusCode);
        }

        return $this->respondSuccess(null, __(CoreTranslationKey::USER_DETAIL_IMAGE_DELETED->value));
    }

    public function nearby(NearbyUsersRequest $request): JsonResponse
    {
        $users = $this->nearbyUsersService->findNearbyUsers(
            $request->latitude(),
            $request->longitude(),
            $request->radius(),
            $request->perPage()
        );

        return $this->respondSuccess([
            'users' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'search_params' => [
                'latitude' => $request->latitude(),
                'longitude' => $request->longitude(),
                'radius_km' => $request->radius(),
            ],
        ]);
    }

    private function resolveUserOrResponse(Request $request): User|JsonResponse
    {
        $authenticated = $request->user();

        if ($authenticated instanceof User) {
            return $authenticated;
        }

        $routeUserId = $request->route(self::ROUTE_PARAM_USER);

        if (! $routeUserId) {
            return $this->respondError(__(CoreTranslationKey::USER_NOT_FOUND->value), 404);
        }

        try {
            return User::query()->findOrFail((int) $routeUserId);
        } catch (ModelNotFoundException) {
            return $this->respondError(__(CoreTranslationKey::USER_NOT_FOUND->value), 404);
        }
    }
}
