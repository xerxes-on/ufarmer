<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\ServiceCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\JobsServices\Filament\Resources\ServiceCategoryResource;

class EditServiceCategory extends EditRecord
{
    protected static string $resource = ServiceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
