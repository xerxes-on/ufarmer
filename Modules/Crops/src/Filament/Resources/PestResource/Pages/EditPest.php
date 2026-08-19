<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\PestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\PestResource;

class EditPest extends EditRecord
{
    protected static string $resource = PestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
