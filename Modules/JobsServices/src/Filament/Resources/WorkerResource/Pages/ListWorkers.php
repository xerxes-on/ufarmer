<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\WorkerResource\Pages;

use App\Support\AiImport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\JobsServices\Filament\Resources\AiImportResource;
use Modules\JobsServices\Filament\Resources\WorkerResource;

class ListWorkers extends ListRecords
{
    protected static string $resource = WorkerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A link rather than a modal: the import is a multi-step flow
            // (upload → extract → review → publish) that needs its own pages.
            // Hidden entirely when the feature is off or unconfigured, so no
            // one discovers it by clicking a button that cannot work.
            Actions\Action::make('aiImport')
                ->label(__('admin-panel.resources.ai_import.actions.ai_import'))
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->visible(fn (): bool => AiImport::enabled() && AiImportResource::canCreate())
                ->url(fn (): string => AiImportResource::getUrl('create')),
            Actions\CreateAction::make(),
        ];
    }
}
