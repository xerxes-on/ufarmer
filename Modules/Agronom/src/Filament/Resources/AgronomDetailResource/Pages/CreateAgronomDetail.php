<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Resources\AgronomDetailResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Agronom\Filament\Resources\AgronomDetailResource;

class CreateAgronomDetail extends CreateRecord
{
    protected static string $resource = AgronomDetailResource::class;
}
