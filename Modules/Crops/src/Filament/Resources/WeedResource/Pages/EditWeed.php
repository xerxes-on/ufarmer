<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\WeedResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Crops\Filament\Resources\WeedResource;

class EditWeed extends EditRecord
{
    protected static string $resource = WeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
