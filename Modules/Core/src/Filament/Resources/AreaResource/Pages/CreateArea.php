<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\AreaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Core\Filament\Resources\AreaResource;

class CreateArea extends CreateRecord
{
    protected static string $resource = AreaResource::class;
}
