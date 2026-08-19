<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\Support;

use InvalidArgumentException;
use Makhweb\BillingClient\DTO\FloatFixerDTO;

class CurrencyRateNormalizer
{
    public static function normalize(float $amount, float $rate, int $roundingMode, int $scale): float
    {
        $raw = pow(10, $scale);

        if ($roundingMode === FloatFixerDTO::ROUNDING_MODE_UP) {
            return ceil(($amount * $rate) * $raw) / $raw;
        }

        if ($roundingMode === FloatFixerDTO::ROUNDING_MODE_DOWN) {
            return floor(($amount * $rate) * $raw) / $raw;
        }

        throw new InvalidArgumentException('Invalid rounding mode');
    }
}
