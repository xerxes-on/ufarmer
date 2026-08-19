<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\PestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Crops\Filament\Resources\PestResource;

class CreatePest extends CreateRecord
{
    protected static string $resource = PestResource::class;
}
