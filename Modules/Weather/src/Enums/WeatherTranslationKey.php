<?php

declare(strict_types=1);

namespace Modules\Weather\Enums;

enum WeatherTranslationKey: string
{
    // Validation messages
    case ValidationLatitudeRequired = 'weather::messages.validation.latitude_required';
    case ValidationLatitudeNumeric = 'weather::messages.validation.latitude_numeric';
    case ValidationLatitudeBetween = 'weather::messages.validation.latitude_between';
    case ValidationLongitudeRequired = 'weather::messages.validation.longitude_required';
    case ValidationLongitudeNumeric = 'weather::messages.validation.longitude_numeric';
    case ValidationLongitudeBetween = 'weather::messages.validation.longitude_between';
    case ValidationCropIdRequired = 'weather::messages.validation.crop_id_required';
    case ValidationCropIdExists = 'weather::messages.validation.crop_id_exists';
    case ValidationCropUuidRequired = 'weather::messages.validation.crop_uuid_required';
    case ValidationCropUuidExists = 'weather::messages.validation.crop_uuid_exists';
}
