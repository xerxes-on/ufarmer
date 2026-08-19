<?php

declare(strict_types=1);

namespace Modules\General\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\General\Models\Story;

final class StoryService
{
    public function paginateStories(bool $includeInactive, int $perPage): LengthAwarePaginator
    {
        $query = Story::query();

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query
            ->latest()
            ->paginate($perPage);
    }
}
