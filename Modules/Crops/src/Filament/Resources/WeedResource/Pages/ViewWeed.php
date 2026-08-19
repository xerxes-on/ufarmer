<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\WeedResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Crops\Filament\Resources\WeedResource;

class ViewWeed extends ViewRecord
{
    protected static string $resource = WeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
