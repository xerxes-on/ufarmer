<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament\Resources\ExporterRoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Exporter\Filament\Resources\ExporterRoleResource;

class EditExporterRole extends EditRecord
{
    protected static string $resource = ExporterRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
