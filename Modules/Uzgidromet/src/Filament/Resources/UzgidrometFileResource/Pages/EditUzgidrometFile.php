<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource;
use Modules\Uzgidromet\Models\UzgidrometFile;

class EditUzgidrometFile extends EditRecord
{
    protected static string $resource = UzgidrometFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label(__('uzgidromet::filament.actions.download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    /** @var UzgidrometFile $record */
                    $record = $this->getRecord();

                    return Storage::disk('s3')->download($record->file_path, $record->original_name);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
