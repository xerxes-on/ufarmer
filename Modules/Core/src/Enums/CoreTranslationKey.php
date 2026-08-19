<?php

declare(strict_types=1);

namespace Modules\Core\Enums;

enum CoreTranslationKey: string
{
    case AUTH_UNAUTHORIZED = 'core::messages.auth.unauthorized';

    case AREA_CREATED = 'core::messages.area.created';
    case AREA_UPDATED = 'core::messages.area.updated';
    case AREA_DELETED = 'core::messages.area.deleted';
    case AREA_UNIT_M2 = 'core::messages.area.unit_m2';
    case AREA_UNIT_HA = 'core::messages.area.unit_ha';

    case AREA_CROP_ATTACHED = 'core::messages.area_crop.attached';
    case AREA_CROP_UPDATED = 'core::messages.area_crop.updated';
    case AREA_CROP_DETACHED = 'core::messages.area_crop.detached';
    case AREA_CROP_NONE_ATTACHED = 'core::messages.area_crop.none_attached';
    case AREA_CROP_ACTIVATED = 'core::messages.area_crop.activated';
    case AREA_CROP_DEACTIVATED = 'core::messages.area_crop.deactivated';
    case AREA_CROP_MISSING_CROP = 'core::messages.area_crop.missing_crop';
    case AREA_CROP_NOT_FOUND = 'core::messages.area_crop.not_found';
    case AREA_CROP_INACTIVE_CROP = 'core::messages.area_crop.inactive_crop';
    case AREA_CROP_HARVESTED = 'core::messages.area_crop.harvested';
    case AREA_CROP_ALREADY_HARVESTED = 'core::messages.area_crop.already_harvested';
    case AREA_CROP_INSUFFICIENT_SPACE = 'core::messages.area_crop.insufficient_space';
    case AREA_CROP_EXCEEDS_TOTAL = 'core::messages.area_crop.exceeds_total';
    case AREA_CROP_INVALID_AREA = 'core::messages.area_crop.invalid_area';
    case REGION_NOT_FOUND = 'core::messages.region.not_found';
    case USER_DETAIL_CREATED = 'core::messages.user_detail.created';
    case USER_DETAIL_UPDATED = 'core::messages.user_detail.updated';
    case USER_DETAIL_DELETED = 'core::messages.user_detail.deleted';
    case USER_DETAIL_ALREADY_EXISTS = 'core::messages.user_detail.already_exists';
    case USER_DETAIL_NOT_FOUND = 'core::messages.user_detail.not_found';
    case USER_DETAIL_IMAGE_DELETED = 'core::messages.user_detail.image_deleted';
    case USER_DETAIL_CITY_MISMATCH = 'core::messages.user_detail.city_mismatch';
    case USER_DETAIL_NO_IMAGE = 'core::messages.user_detail.no_image';
    case USER_NOT_FOUND = 'core::messages.user.not_found';

    case NEARBY_USERS_FOUND = 'core::messages.nearby_users.found';
    case NEARBY_USERS_NONE_FOUND = 'core::messages.nearby_users.none_found';
    case NEARBY_USERS_INVALID_COORDINATES = 'core::messages.nearby_users.invalid_coordinates';
}
