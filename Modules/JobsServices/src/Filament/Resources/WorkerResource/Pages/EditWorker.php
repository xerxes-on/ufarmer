<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\WorkerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\JobsServices\Filament\Resources\WorkerResource;

class EditWorker extends EditRecord
{
    protected static string $resource = WorkerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
