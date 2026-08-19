<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\JobAnnouncementResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\JobsServices\Filament\Resources\JobAnnouncementResource;

class CreateJobAnnouncement extends CreateRecord
{
    protected static string $resource = JobAnnouncementResource::class;
}
