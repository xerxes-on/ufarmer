<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\AiImportResource\Pages;

use App\Jobs\ParseAiImportJob;
use App\Support\AiImport;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Filament\Resources\AiImportResource;

class CreateAiImport extends CreateRecord
{
    protected static string $resource = AiImportResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['entity_type'] ??= AiImportEntity::WORKER->value;
        $data['status'] = AiImportStatus::Pending->value;
        // Recorded so a re-parse months later reproduces the original run
        // rather than following whoever happens to be viewing it.
        $data['locale'] = app()->getLocale();
        $data['created_by'] = auth()->id();

        if (($data['source_type'] ?? null) === 'upload') {
            $data['disk'] = 'local';
            $data['size'] = $this->uploadedSize((string) ($data['path'] ?? ''));
        } else {
            // Switching source type mid-form can leave the other branch's
            // value behind; clear it so the parser reads the right one.
            $data['path'] = null;
            $data['disk'] = null;
            $data['original_filename'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! AiImport::usable()) {
            $this->record->forceFill([
                'status' => AiImportStatus::Failed,
                'error_message' => __('admin-panel.resources.ai_import.errors.not_configured'),
            ])->save();

            Notification::make()
                ->danger()
                ->title(__('admin-panel.resources.ai_import.errors.not_configured'))
                ->send();

            return;
        }

        ParseAiImportJob::dispatch((int) $this->record->getKey());

        Notification::make()
            ->success()
            ->title(__('admin-panel.resources.ai_import.notifications.queued'))
            ->body(__('admin-panel.resources.ai_import.notifications.queued_body'))
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return ReviewAiImport::getUrl(['record' => $this->record]);
    }

    private function uploadedSize(string $path): int
    {
        if ($path === '') {
            return 0;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        return $disk->exists($path) ? (int) $disk->size($path) : 0;
    }
}
