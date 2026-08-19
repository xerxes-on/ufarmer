<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\RecommendationRuleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AgroCalculator\Filament\Resources\RecommendationRuleResource;

class CreateRecommendationRule extends CreateRecord
{
    protected static string $resource = RecommendationRuleResource::class;
}
