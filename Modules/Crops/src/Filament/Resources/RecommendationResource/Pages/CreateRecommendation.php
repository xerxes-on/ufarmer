<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\RecommendationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\RecommendationResource;

class CreateRecommendation extends CreateRecord
{
    protected static string $resource = RecommendationResource::class;
}
