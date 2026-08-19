<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Harvest\Filament\Resources\HarvestResource;

class EditHarvest extends EditRecord
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
