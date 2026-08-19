<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament\Resources\ExporterRoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Exporter\Filament\Resources\ExporterRoleResource;

class CreateExporterRole extends CreateRecord
{
    protected static string $resource = ExporterRoleResource::class;
}
