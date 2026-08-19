<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource;

class CreateUzgidrometFile extends CreateRecord
{
    protected static string $resource = UzgidrometFileResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by_user_id'] = auth()->id();

        return $data;
    }
}
