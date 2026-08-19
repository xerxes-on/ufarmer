<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\AgronomDetailResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Agronom\Filament\Resources\AgronomDetailResource;

class ListAgronomDetails extends ListRecords
{
    protected static string $resource = AgronomDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
