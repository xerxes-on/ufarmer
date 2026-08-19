<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\PestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Crops\Filament\Resources\PestResource;

class ViewPest extends ViewRecord
{
    protected static string $resource = PestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
