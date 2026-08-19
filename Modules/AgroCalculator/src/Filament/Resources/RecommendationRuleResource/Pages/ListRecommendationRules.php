<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources\RecommendationRuleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AgroCalculator\Filament\Resources\RecommendationRuleResource;

class ListRecommendationRules extends ListRecords
{
    protected static string $resource = RecommendationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
