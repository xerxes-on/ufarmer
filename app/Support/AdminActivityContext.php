<?php

declare(strict_types=1);

namespace App\Support;

final class AdminActivityContext
{
    private bool $active = false;

    public function activate(): void
    {
        $this->active = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
