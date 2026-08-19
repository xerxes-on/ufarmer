<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament\Resources\ExporterRoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Exporter\Filament\Resources\ExporterRoleResource;

class ListExporterRoles extends ListRecords
{
    protected static string $resource = ExporterRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
