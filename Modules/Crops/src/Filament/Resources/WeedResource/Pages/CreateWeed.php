<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\WeedResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\WeedResource;

class CreateWeed extends CreateRecord
{
    protected static string $resource = WeedResource::class;
}
