<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Resolves the AuthBridge `auth_id` on every save, unconditionally —
     * deliberately NOT gated behind "only if auth_id is currently null". An
     * employee can have a legacy `auth_id` with zero application attachment,
     * and skipping the call on that assumption means they can never get
     * attached to this panel's application.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $authId = EmployeeResource::resolveAuthId(
            phone: (string) ($data['phone'] ?? $record->phone ?? ''),
            entityId: $record->id,
        );

        if ($authId !== null) {
            $data['auth_id'] = $authId;
        }

        $record->update($data);

        return $record;
    }
}
