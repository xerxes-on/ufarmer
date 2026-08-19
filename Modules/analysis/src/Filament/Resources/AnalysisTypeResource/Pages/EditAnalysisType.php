<?php

declare(strict_types=1);

namespace Modules\Analysis\Filament\Resources\AnalysisTypeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Analysis\Filament\Resources\AnalysisTypeResource;

class EditAnalysisType extends EditRecord
{
    protected static string $resource = AnalysisTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
