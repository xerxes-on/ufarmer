<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\PestDetailResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\PlantScanner\Filament\Resources\PestDetailResource;

class CreatePestDetail extends CreateRecord
{
    protected static string $resource = PestDetailResource::class;
}
