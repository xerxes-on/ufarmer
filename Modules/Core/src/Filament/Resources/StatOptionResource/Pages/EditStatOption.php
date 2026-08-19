<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\StatOptionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Core\Filament\Resources\StatOptionResource;

class EditStatOption extends EditRecord
{
    protected static string $resource = StatOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
