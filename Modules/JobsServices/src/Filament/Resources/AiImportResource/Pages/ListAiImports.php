<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\AiImportResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\JobsServices\Filament\Resources\AiImportResource;

class ListAiImports extends ListRecords
{
    protected static string $resource = AiImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('admin-panel.resources.ai_import.actions.create'))
                ->icon('heroicon-o-sparkles'),
        ];
    }
}
