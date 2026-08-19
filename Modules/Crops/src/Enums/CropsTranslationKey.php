<?php

declare(strict_types=1);

namespace Modules\Crops\Enums;

enum CropsTranslationKey: string
{
    case CROP_NOT_FOUND = 'crops::messages.crop.not_found';
    case CROP_ATTACHED = 'crops::messages.crop.attached';
    case CROP_DETACHED = 'crops::messages.crop.detached';

    case USER_CROP_CREATED = 'crops::messages.user_crop.created';
    case USER_CROP_DELETED = 'crops::messages.user_crop.deleted';
    case USER_CROP_ALREADY_EXISTS = 'crops::messages.user_crop.already_exists';
    case USER_CROP_NOT_FOUND = 'crops::messages.user_crop.not_found';
    case USER_CROP_SYNCED = 'crops::messages.user_crop.synced';

    case CROP_AREA_CREATED = 'crops::messages.crop_area.created';
    case CROP_AREA_DELETED = 'crops::messages.crop_area.deleted';
    case CROP_AREA_NOT_FOUND = 'crops::messages.crop_area.not_found';
    case CROP_AREA_UNAUTHORIZED = 'crops::messages.crop_area.unauthorized';
    case GENERAL_SUCCESS = 'crops::messages.general.success';

    case SEEDER_REGIONS_SEEDED = 'crops::messages.seeder.regions_seeded';
    case SEEDER_REGIONS_ALREADY_SEEDED = 'crops::messages.seeder.regions_already_seeded';
    case SEEDER_REGIONS_CLEARED = 'crops::messages.seeder.regions_cleared';
    case SEEDER_REGIONS_FAILED = 'crops::messages.seeder.regions_failed';
    case SEEDER_REGIONS_CLEAR_FAILED = 'crops::messages.seeder.regions_clear_failed';
    case SEEDER_CROPS_SEEDED = 'crops::messages.seeder.crops_seeded';
    case SEEDER_CROPS_ALREADY_SEEDED = 'crops::messages.seeder.crops_already_seeded';
    case SEEDER_CROPS_CLEARED = 'crops::messages.seeder.crops_cleared';
    case SEEDER_CROPS_FAILED = 'crops::messages.seeder.crops_failed';
    case SEEDER_CROPS_CLEAR_FAILED = 'crops::messages.seeder.crops_clear_failed';
    case SEEDER_STATS_GENERATED = 'crops::messages.seeder.stats_generated';
}
