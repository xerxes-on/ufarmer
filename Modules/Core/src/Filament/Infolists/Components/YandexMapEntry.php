<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

class YandexMapEntry extends Entry
{
    protected string $view = 'core::filament.infolists.components.yandex-map-entry';

    protected int $height = 400;

    protected int $zoom = 14;

    public function height(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function zoom(int $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getZoom(): int
    {
        return $this->zoom;
    }
}
