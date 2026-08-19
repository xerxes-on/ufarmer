<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Models\Area;

final class PaginatedAreasData
{
    public function __construct(private readonly LengthAwarePaginator $paginator) {}

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self($paginator);
    }

    public function withAvailableArea(): LengthAwarePaginator
    {
        $this->paginator->setCollection(
            $this->paginator->getCollection()->map(function ($item) {
                if ($item instanceof Area) {
                    $item->setAttribute('available_area', $item->getAvailableArea());
                }

                return $item;
            })
        );

        return $this->paginator;
    }
}
