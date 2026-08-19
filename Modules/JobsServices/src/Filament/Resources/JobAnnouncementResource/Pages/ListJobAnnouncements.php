<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\JobAnnouncementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\JobsServices\Filament\Resources\JobAnnouncementResource;

class ListJobAnnouncements extends ListRecords
{
    protected static string $resource = JobAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
