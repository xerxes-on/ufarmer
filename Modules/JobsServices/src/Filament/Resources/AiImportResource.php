<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Support\AiImport;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportStatus;
use Modules\JobsServices\Filament\Resources\AiImportResource\Pages;
use Modules\JobsServices\Filament\Resources\AiImportResource\RelationManagers;
use Modules\JobsServices\Models\AiImportBatch;
use Modules\JobsServices\Rules\ReadableGoogleSheet;

/**
 * AI-assisted spreadsheet imports (UFARM-2644).
 *
 * Listed in the navigation next to Workers, which is what it fills. It was
 * originally reachable only from the "AI import" button on /admin/workers, on
 * the reasoning that an import is something you start from the list of the
 * thing you are importing — but a batch outlives the moment it is created:
 * reviewing, correcting and publishing it are return visits, and a screen you
 * cannot navigate back to is one nobody finishes (UFARM-2671).
 *
 * Permissions ride on the existing `worker` Shield abilities rather than a new
 * set — importing workers is creating workers, and a second permission would
 * only be a way for the two to disagree. `canAccess()` still hides the entry
 * entirely when the feature is switched off.
 */
class AiImportResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AiImportBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    // Directly after Workers (30), the resource it feeds.
    protected static ?int $navigationSort = 40;

    protected static function translationKey(): string
    {
        return 'ai_import';
    }

    public static function canAccess(): bool
    {
        return AiImport::enabled() && auth()->user()?->can('viewAny', \Modules\JobsServices\Models\UserProfile::class);
    }

    public static function canCreate(): bool
    {
        return self::canAccess() && auth()->user()?->can('create', \Modules\JobsServices\Models\UserProfile::class);
    }

    /** A batch is a record of what happened; correcting it happens per row. */
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('admin-panel.resources.ai_import.sections.source'))
                ->schema([
                    Forms\Components\Placeholder::make('flow')
                        ->hiddenLabel()
                        ->content(__('admin-panel.resources.ai_import.help.flow'))
                        ->columnSpanFull(),

                    Forms\Components\Select::make('entity_type')
                        ->label(__('admin-panel.resources.ai_import.fields.entity_type'))
                        ->options(AiImportEntity::options())
                        ->default(AiImportEntity::WORKER->value)
                        ->required()
                        ->native(false)
                        // Only one importer exists today; the field is here so
                        // adding a second needs no form change.
                        ->visible(fn (): bool => count(AiImportEntity::cases()) > 1)
                        ->columnSpanFull(),

                    Forms\Components\Radio::make('source_type')
                        ->label(__('admin-panel.resources.ai_import.fields.source_type'))
                        ->options([
                            'google_sheet' => __('admin-panel.resources.ai_import.source_types.google_sheet'),
                            'upload' => __('admin-panel.resources.ai_import.source_types.upload'),
                        ])
                        ->default('google_sheet')
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('source_url')
                        ->label(__('admin-panel.resources.ai_import.fields.source_url'))
                        ->helperText(__('admin-panel.resources.ai_import.help.source_url'))
                        ->url()
                        ->maxLength(1000)
                        ->required(fn (Forms\Get $get): bool => $get('source_type') === 'google_sheet')
                        ->visible(fn (Forms\Get $get): bool => $get('source_type') === 'google_sheet')
                        // Validate both the URL shape and anonymous CSV access
                        // before creating a batch. Otherwise a typo, private
                        // sheet, or missing tab fails later in the queue.
                        ->rule(new ReadableGoogleSheet)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('path')
                        ->label(__('admin-panel.resources.ai_import.fields.file'))
                        ->helperText(__('admin-panel.resources.ai_import.help.file'))
                        ->disk('local')
                        ->directory('ai-imports')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(AiImport::maxUploadKb())
                        ->storeFileNamesIn('original_filename')
                        ->required(fn (Forms\Get $get): bool => $get('source_type') === 'upload')
                        ->visible(fn (Forms\Get $get): bool => $get('source_type') === 'upload')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('createdBy'))
            // Extraction is queued, so a freshly created batch has to update
            // itself or the admin is left watching a stale "Queued".
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label(__('admin-panel.resources.ai_import.fields.entity_type'))
                    ->formatStateUsing(fn (AiImportEntity $state): string => $state->label())
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')
                    ->label(__('admin-panel.resources.ai_import.fields.source_type'))
                    ->getStateUsing(fn (AiImportBatch $record): string => $record->original_filename
                        ?: (string) $record->source_url)
                    ->limit(50)
                    ->tooltip(fn (AiImportBatch $record): ?string => $record->source_url)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin-panel.resources.ai_import.fields.status'))
                    ->formatStateUsing(fn (AiImportStatus $state): string => $state->label())
                    ->color(fn (AiImportStatus $state): string => $state->color())
                    ->badge(),
                Tables\Columns\TextColumn::make('rows_total')
                    ->label(__('admin-panel.resources.ai_import.fields.rows_total'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('rows_valid')
                    ->label(__('admin-panel.resources.ai_import.fields.rows_valid'))
                    ->numeric()
                    ->color('success'),
                Tables\Columns\TextColumn::make('rows_published')
                    ->label(__('admin-panel.resources.ai_import.fields.rows_published'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('model_used')
                    ->label(__('admin-panel.resources.ai_import.fields.model_used'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cost')
                    ->label(__('admin-panel.resources.ai_import.fields.cost'))
                    ->money('USD', divideBy: 1)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('admin-panel.resources.ai_import.fields.created_by'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin-panel.resources.ai_import.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin-panel.resources.ai_import.fields.status'))
                    ->options(AiImportStatus::options())
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label(__('admin-panel.resources.ai_import.actions.review'))
                    ->icon('heroicon-m-eye')
                    ->url(fn (AiImportBatch $record): string => Pages\ReviewAiImport::getUrl(['record' => $record])),
            ])
            ->recordUrl(fn (AiImportBatch $record): string => Pages\ReviewAiImport::getUrl(['record' => $record]))
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RowsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiImports::route('/'),
            'create' => Pages\CreateAiImport::route('/create'),
            'view' => Pages\ReviewAiImport::route('/{record}'),
        ];
    }
}
