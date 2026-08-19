<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament\Resources\ExporterProfileResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Modules\Exporter\Filament\Resources\ExporterProfileResource;
use Modules\Exporter\Models\ExporterProfile;

class EditExporterProfile extends EditRecord
{
    protected static string $resource = ExporterProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ExporterProfile $record */
        $newRoleId = $data['exporter_role_id'] ?? null;
        $roleChanged = $newRoleId !== null && (int) $newRoleId !== (int) $record->exporter_role_id;

        if ($roleChanged && ExporterProfileResource::blocksUnlimitedRoleAssignment($record, (int) $newRoleId)) {
            Notification::make()
                ->title(__('exporter::filament.resources.profile.notifications.ineligible_for_unlimited_role'))
                ->danger()
                ->send();

            throw new Halt;
        }

        $record->update($data);

        return $record;
    }
}
