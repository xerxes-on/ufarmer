<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\LegalConfigResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\General\Filament\Resources\LegalConfigResource;

class CreateLegalConfig extends CreateRecord
{
    protected static string $resource = LegalConfigResource::class;
}
