<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\JobAnnouncementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\JobsServices\Filament\Resources\JobAnnouncementResource;

class EditJobAnnouncement extends EditRecord
{
    protected static string $resource = JobAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
