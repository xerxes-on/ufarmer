<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\WeedResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Crops\Filament\Resources\WeedResource;

class ListWeeds extends ListRecords
{
    protected static string $resource = WeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
