<?php

declare(strict_types=1);

namespace Modules\General\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\General\Enums\GeneralTranslationKey;
use Modules\General\Exceptions\GeneralException;
use Modules\General\Models\ProductStat;
use Xerxes\AuthBridge\Models\Farmer;

final class ProductStatService
{
    public function getTodayStat(): ProductStat
    {
        $stat = ProductStat::whereDate('date', now()->toDateString())->first();

        if (! $stat) {
            throw new GeneralException(GeneralTranslationKey::PRODUCT_STAT_TODAY_MISSING->value, 404);
        }

        return $stat;
    }

    public function getCropHistory(Farmer $farmer, int $cropId, CropPriceHistoryService $service): array
    {
        $hasAccess = $farmer->crops()->where('crops.id', $cropId)->exists();

        if (! $hasAccess) {
            throw new GeneralException(GeneralTranslationKey::PRODUCT_STAT_CROP_FORBIDDEN->value, 403);
        }

        try {
            return $service->getCropPriceHistory($cropId);
        } catch (ModelNotFoundException) {
            throw new GeneralException(GeneralTranslationKey::PRODUCT_STAT_CROP_NOT_FOUND->value, 404);
        }
    }
}
