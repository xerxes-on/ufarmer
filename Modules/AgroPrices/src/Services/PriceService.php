<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Services;

use Illuminate\Support\Collection;
use Modules\AgroPrices\Models\DailyPrice;

final class PriceService
{
    /**
     * @return Collection<int, DailyPrice>
     */
    public function getTodayPrices(): Collection
    {
        return DailyPrice::query()
            ->with('product')
            ->orderBy('id')
            ->get();
    }
}
