<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\LegalConfigResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\General\Filament\Resources\LegalConfigResource;

class EditLegalConfig extends EditRecord
{
    protected static string $resource = LegalConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
