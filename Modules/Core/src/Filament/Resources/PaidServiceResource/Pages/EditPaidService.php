<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\PaidServiceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Core\Filament\Resources\PaidServiceResource;

class EditPaidService extends EditRecord
{
    protected static string $resource = PaidServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
