<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\SpecializationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Agronom\Filament\Resources\SpecializationResource;

class EditSpecialization extends EditRecord
{
    protected static string $resource = SpecializationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
