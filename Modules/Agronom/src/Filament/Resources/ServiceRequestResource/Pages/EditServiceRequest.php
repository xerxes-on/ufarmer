<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\ServiceRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Agronom\Filament\Resources\ServiceRequestResource;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
