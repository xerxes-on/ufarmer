<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\PaidServiceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Core\Filament\Resources\PaidServiceResource;

class CreatePaidService extends CreateRecord
{
    protected static string $resource = PaidServiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
