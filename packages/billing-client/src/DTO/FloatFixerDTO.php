<?php

declare(strict_types=1);

namespace Makhweb\BillingClient\DTO;

class FloatFixerDTO
{
    const ROUNDING_MODE_UP = 1;

    const ROUNDING_MODE_DOWN = 0;

    public int $scale;

    public int $roundingMode;

    public float $rate;

    public function setRoundingMode(string $roundingMode): self
    {
        $this->roundingMode = $roundingMode;

        return $this;
    }

    public function setScale(int $scale): self
    {
        $this->scale = $scale;

        return $this;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'rounding_mode' => $this->roundingMode,
            'scale' => $this->scale,
            'rate' => $this->rate,
        ];
    }
}
