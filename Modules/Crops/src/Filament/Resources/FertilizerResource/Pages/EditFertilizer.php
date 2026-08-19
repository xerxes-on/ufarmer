<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\FertilizerResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\FertilizerResource;

class EditFertilizer extends EditRecord
{
    protected static string $resource = FertilizerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
