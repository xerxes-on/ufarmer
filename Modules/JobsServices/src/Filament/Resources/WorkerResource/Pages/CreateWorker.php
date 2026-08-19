<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\WorkerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\JobsServices\Filament\Resources\WorkerResource;

class CreateWorker extends CreateRecord
{
    protected static string $resource = WorkerResource::class;

    /**
     * Land on Edit after creation so the Services relation manager (only
     * rendered once the profile exists) is immediately reachable — creating
     * a worker and then adding their services is one continuous task.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
