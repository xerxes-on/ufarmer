<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\AiImportResource\Pages;

use App\Jobs\ParseAiImportJob;
use App\Support\AiImport;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportRowStatus;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Filament\Resources\AiImportResource;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Services\AiImport\Worker\PublishWorkerImportAction;

/**
 * Review screen: the human checkpoint between the AI and the database.
 *
 * Nothing an import produces becomes a real worker until someone reads it here
 * and presses Publish.
 */
class ReviewAiImport extends ViewRecord
{
    protected static string $resource = AiImportResource::class;

    /**
     * Poll only while the queue is actually working. A finished batch that
     * kept polling would hammer the server for no new information.
     */
    public function getPollingInterval(): ?string
    {
        return $this->record->isInProgress() ? '5s' : null;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make(__('admin-panel.resources.ai_import.sections.summary'))
                ->schema([
                    Infolists\Components\TextEntry::make('status')
                        ->label(__('admin-panel.resources.ai_import.fields.status'))
                        ->formatStateUsing(fn (AiImportStatus $state): string => $state->label())
                        ->color(fn (AiImportStatus $state): string => $state->color())
                        ->badge(),
                    Infolists\Components\TextEntry::make('entity_type')
                        ->label(__('admin-panel.resources.ai_import.fields.entity_type'))
                        ->formatStateUsing(fn (AiImportEntity $state): string => $state->label()),
                    Infolists\Components\TextEntry::make('source')
                        ->label(__('admin-panel.resources.ai_import.fields.source_type'))
                        ->getStateUsing(fn (AiImportBatch $record): string => $record->original_filename
                            ?: (string) $record->source_url)
                        ->url(fn (AiImportBatch $record): ?string => $record->source_url)
                        ->openUrlInNewTab()
                        ->placeholder('-'),
                    Infolists\Components\TextEntry::make('rows_total')
                        ->label(__('admin-panel.resources.ai_import.fields.rows_total')),
                    // Shown only when the model returned fewer records than the
                    // sheet holds. Silence used to be the only symptom of a
                    // short extraction.
                    Infolists\Components\TextEntry::make('rows_expected')
                        ->label(__('admin-panel.resources.ai_import.fields.rows_expected'))
                        ->getStateUsing(fn (AiImportBatch $record): int => $record->stat('expected_rows'))
                        ->color('danger')
                        ->visible(fn (AiImportBatch $record): bool => $record->stat('expected_rows')
                            > $record->stat('extracted_rows')),
                    Infolists\Components\TextEntry::make('rows_valid')
                        ->label(__('admin-panel.resources.ai_import.fields.rows_valid'))
                        ->color('success'),
                    Infolists\Components\TextEntry::make('rows_published')
                        ->label(__('admin-panel.resources.ai_import.fields.rows_published')),
                    Infolists\Components\TextEntry::make('workers_created')
                        ->label(__('admin-panel.resources.ai_import.fields.workers_created'))
                        ->getStateUsing(fn (AiImportBatch $record): int => $record->stat('workers_created'))
                        ->visible(fn (AiImportBatch $record): bool => $record->status === AiImportStatus::Published),
                    Infolists\Components\TextEntry::make('offers_created')
                        ->label(__('admin-panel.resources.ai_import.fields.offers_created'))
                        ->getStateUsing(fn (AiImportBatch $record): int => $record->stat('offers_created'))
                        ->visible(fn (AiImportBatch $record): bool => $record->status === AiImportStatus::Published),
                    Infolists\Components\TextEntry::make('error_message')
                        ->label(__('admin-panel.resources.ai_import.fields.error'))
                        ->color('danger')
                        ->visible(fn (AiImportBatch $record): bool => filled($record->error_message))
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Infolists\Components\Section::make(__('admin-panel.resources.ai_import.sections.usage'))
                ->schema([
                    Infolists\Components\TextEntry::make('model_used')
                        ->label(__('admin-panel.resources.ai_import.fields.model_used'))
                        ->placeholder('-'),
                    Infolists\Components\TextEntry::make('tokens')
                        ->label(__('admin-panel.resources.ai_import.fields.tokens'))
                        ->getStateUsing(fn (AiImportBatch $record): string => $record->prompt_tokens === null
                            ? '-'
                            : sprintf('%s / %s', $record->prompt_tokens, $record->completion_tokens ?? 0)),
                    Infolists\Components\TextEntry::make('cost')
                        ->label(__('admin-panel.resources.ai_import.fields.cost'))
                        ->placeholder('-'),
                ])
                ->columns(3)
                ->collapsed(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publish')
                ->label(__('admin-panel.resources.ai_import.actions.publish'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => __(
                    'admin-panel.resources.ai_import.actions.publish_confirm',
                    ['count' => $this->record->publishableRows()->count()],
                ))
                ->visible(fn (): bool => $this->record->isPublishable())
                ->disabled(fn (): bool => $this->record->publishableRows()->count() === 0)
                ->action(fn () => $this->publish()),

            Actions\Action::make('reopen')
                ->label(__('admin-panel.resources.ai_import.actions.reopen'))
                ->icon('heroicon-o-lock-open')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(__('admin-panel.resources.ai_import.actions.reopen_confirm'))
                // Drafts, not publishable drafts: the rows worth reopening for
                // are precisely the ones still blocked on something an admin
                // has to fix by hand.
                ->visible(fn (): bool => $this->record->status === AiImportStatus::Published
                    && $this->record->rows()->where('status', AiImportRowStatus::Draft)->exists())
                ->action(fn () => $this->reopen()),

            Actions\Action::make('reparse')
                ->label(__('admin-panel.resources.ai_import.actions.reparse'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('admin-panel.resources.ai_import.actions.reparse_confirm'))
                ->visible(fn (): bool => $this->record->isReparsable())
                ->action(fn () => $this->reparse()),
        ];
    }

    private function publish(): void
    {
        $result = (new PublishWorkerImportAction)->execute($this->record);

        if ($result['published'] === 0) {
            Notification::make()
                ->warning()
                ->title(__('admin-panel.resources.ai_import.notifications.nothing_published'))
                ->body($this->failureBody($result))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('admin-panel.resources.ai_import.notifications.published', ['count' => $result['published']]))
            ->body(__('admin-panel.resources.ai_import.notifications.published_body', [
                'workers' => $result['workers_created'],
                'offers' => $result['offers_created'],
            ]).$this->failureBody($result))
            ->send();

        $this->refreshFormData(['status']);
    }

    /**
     * Hand a finished batch back for a second pass.
     *
     * Batches published before publishing became resumable were marked
     * Published even when most of their rows had failed to publish, which hid
     * the Publish button for good. This returns such a batch to review without
     * touching a single row: the published ones keep their created records,
     * and `rows_published` stays non-zero so re-parsing still refuses to wipe
     * them.
     */
    private function reopen(): void
    {
        $this->record->forceFill(['status' => AiImportStatus::Extracted])->save();

        Notification::make()
            ->success()
            ->title(__('admin-panel.resources.ai_import.notifications.reopened'))
            ->send();
    }

    private function reparse(): void
    {
        if (! AiImport::usable()) {
            Notification::make()
                ->danger()
                ->title(__('admin-panel.resources.ai_import.errors.not_configured'))
                ->send();

            return;
        }

        $this->record->forceFill([
            'status' => AiImportStatus::Pending,
            'error_message' => null,
        ])->save();

        // Release the ShouldBeUnique lock first. Its window (30 min) is longer
        // than the staleness window that offers this button (15 min), so a
        // retry on an abandoned batch would otherwise be silently swallowed by
        // the lock its own dead run left behind.
        $this->releaseUniqueLock();

        ParseAiImportJob::dispatch((int) $this->record->getKey());

        Notification::make()
            ->success()
            ->title(__('admin-panel.resources.ai_import.notifications.reparse_queued'))
            ->send();
    }

    /**
     * Drop the ShouldBeUnique lock left by a previous dispatch of this batch.
     *
     * Laravel keys the lock on the job class plus uniqueId(); it is normally
     * released when the job finishes, which never happened for a batch whose
     * worker died or whose queue nobody consumed.
     */
    private function releaseUniqueLock(): void
    {
        $job = new ParseAiImportJob((int) $this->record->getKey());

        Cache::lock('laravel_unique_job:'.$job::class.$job->uniqueId())->forceRelease();
    }

    /**
     * Skipped rows and the first few reasons. Truncated on purpose: a
     * notification listing 40 failures is a wall of text nobody reads.
     *
     * @param  array{published: int, skipped: int, errors: array<int, string>}  $result
     */
    private function failureBody(array $result): string
    {
        if ($result['skipped'] === 0) {
            return '';
        }

        $body = "\n".__('admin-panel.resources.ai_import.notifications.skipped', ['count' => $result['skipped']]);

        foreach (array_slice($result['errors'], 0, 3) as $error) {
            $body .= "\n".Str::limit($error, 200);
        }

        return $body;
    }
}
