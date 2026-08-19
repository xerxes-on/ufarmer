<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\PlantScanner\Enums\ScanStatus;
use Modules\PlantScanner\Filament\Resources\ScannedPlantResource\Pages;
use Modules\PlantScanner\Models\ScannedPlant;

class ScannedPlantResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = ScannedPlant::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static ?string $navigationLabel = 'Scanned Plants';

    protected static ?string $navigationGroup = NavigationGroup::Diagnostics->value;

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Scan Information')
                    ->schema([
                        Forms\Components\Placeholder::make('result_name_en')
                            ->label('Result (EN)')
                            ->content(fn (?ScannedPlant $record): string => $record?->resultNames()['en'] ?? 'Not available'),
                        Forms\Components\Placeholder::make('result_scientific_name')
                            ->label('Scientific Name')
                            ->content(fn (?ScannedPlant $record): string => $record?->resultScientificName() ?? 'Not available'),
                        Forms\Components\Placeholder::make('scan_status')
                            ->label('Status')
                            ->content(fn (?ScannedPlant $record): string => $record?->status?->name ?? 'Unknown'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Result')
                    ->schema([
                        Infolists\Components\TextEntry::make('result_name_en')
                            ->label('English')
                            ->getStateUsing(fn (ScannedPlant $record): ?string => $record->resultNames()['en'])
                            ->placeholder('Not available'),
                        Infolists\Components\TextEntry::make('result_name_ru')
                            ->label('Russian')
                            ->getStateUsing(fn (ScannedPlant $record): ?string => $record->resultNames()['ru'])
                            ->placeholder('Not available'),
                        Infolists\Components\TextEntry::make('result_name_uz')
                            ->label('Uzbek')
                            ->getStateUsing(fn (ScannedPlant $record): ?string => $record->resultNames()['uz'])
                            ->placeholder('Not available'),
                        Infolists\Components\TextEntry::make('result_scientific_name')
                            ->label('Scientific Name')
                            ->getStateUsing(fn (ScannedPlant $record): ?string => $record->resultScientificName())
                            ->placeholder('Not available'),
                    ])
                    ->columns(4),
                Infolists\Components\Section::make('Scan Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (ScanStatus $state): string => $state->name)
                            ->color(fn (ScanStatus $state): string => $state->color()),
                        Infolists\Components\TextEntry::make('scan_mode')
                            ->label('Scan Mode')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => str((string) $state)->replace('_', ' ')->title()->toString()),
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('User'),
                        Infolists\Components\TextEntry::make('ai_provider_used')
                            ->label('Provider')
                            ->placeholder('Not recorded'),
                    ])
                    ->columns(4),
                Infolists\Components\Section::make('Image Evidence')
                    ->description('The farmer upload is shown separately from images found or added during analysis.')
                    ->schema([
                        Infolists\Components\ImageEntry::make('uploaded_image')
                            ->label('User Upload')
                            ->key('uploaded-image')
                            ->getStateUsing(fn (ScannedPlant $record): ?string => $record->uploadedImageUrl())
                            ->height(180)
                            ->square()
                            ->checkFileExistence(false)
                            ->action(
                                InfolistAction::make('previewUploadedImage')
                                    ->modalHeading('User Upload')
                                    ->modalDescription('Original scan image uploaded by the user.')
                                    ->modalContent(fn (ScannedPlant $record) => view('filament.infolists.image-preview', [
                                        'url' => $record->uploadedImageUrl(),
                                        'alt' => 'User uploaded plant scan image',
                                    ]))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Close')
                                    ->modalWidth(MaxWidth::SevenExtraLarge)
                                    ->visible(fn (ScannedPlant $record): bool => filled($record->uploadedImageUrl()))
                            )
                            ->tooltip('View full image')
                            ->extraImgAttributes([
                                'alt' => 'User uploaded plant scan image',
                                'class' => 'cursor-zoom-in transition-opacity hover:opacity-90',
                                'loading' => 'lazy',
                            ]),
                        Infolists\Components\ImageEntry::make('reference_images')
                            ->label('Found / Added Images')
                            ->getStateUsing(fn (ScannedPlant $record): array => $record->referenceImageUrls())
                            ->height(120)
                            ->square()
                            ->limit(8)
                            ->limitedRemainingText()
                            ->checkFileExistence(false)
                            ->extraImgAttributes([
                                'alt' => 'Reference image for the scan result',
                                'loading' => 'lazy',
                            ]),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Processing')
                    ->schema([
                        Infolists\Components\TextEntry::make('processing_started_at')
                            ->label('Started At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('processing_completed_at')
                            ->label('Completed At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('similarity_score')
                            ->label('Similarity Score')
                            ->suffix('%')
                            ->placeholder('Not available'),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('AI Cost')
                    ->schema([
                        Infolists\Components\TextEntry::make('ai_cost_usd')
                            ->label('Total (USD)')
                            ->formatStateUsing(fn (mixed $state): string => '$'.number_format((float) $state, 6)),
                        Infolists\Components\TextEntry::make('ai_cost_uzs')
                            ->label('Approximate Total (UZS)')
                            ->getStateUsing(fn (ScannedPlant $record): string => number_format(
                                (float) $record->ai_cost_usd * (int) config('plantscanner.exchange_rate_usd_uzs', 12800),
                                2
                            ).' UZS'),
                        Infolists\Components\RepeatableEntry::make('ai_usage_details.entries')
                            ->label('Breakdown')
                            ->schema([
                                Infolists\Components\TextEntry::make('operation')
                                    ->placeholder('External API'),
                                Infolists\Components\TextEntry::make('provider'),
                                Infolists\Components\TextEntry::make('model')
                                    ->placeholder('API'),
                                Infolists\Components\TextEntry::make('cost_usd')
                                    ->label('Cost')
                                    ->formatStateUsing(fn (mixed $state): string => '$'.number_format((float) $state, 6)),
                                Infolists\Components\TextEntry::make('prompt_tokens')
                                    ->label('Input Tokens')
                                    ->numeric()
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('completion_tokens')
                                    ->label('Output Tokens')
                                    ->numeric()
                                    ->placeholder('—'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Infolists\Components\Section::make('Failure')
                    ->schema([
                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Error')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (ScannedPlant $record): bool => filled($record->error_message)),
                Infolists\Components\Section::make('AI Enrichment')
                    ->schema([
                        Infolists\Components\ViewEntry::make('ai_enriched_data')
                            ->label('Enriched Data')
                            ->getStateUsing(fn (ScannedPlant $record): string => self::formatJson($record->ai_enriched_data))
                            ->view('filament.infolists.json-entry')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (ScannedPlant $record): bool => filled($record->ai_enriched_data))
                    ->collapsible(),
                Infolists\Components\Section::make('Structured Data')
                    ->schema([
                        Infolists\Components\ViewEntry::make('structured_data')
                            ->label('Data')
                            ->getStateUsing(fn (ScannedPlant $record): string => self::formatJson($record->structured_data))
                            ->view('filament.infolists.json-entry')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\ViewEntry::make('metadata')
                            ->label('Metadata')
                            ->getStateUsing(fn (ScannedPlant $record): string => self::formatJson($record->metadata))
                            ->view('filament.infolists.json-entry')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('uploaded_image')
                    ->label('Image')
                    ->getStateUsing(fn (ScannedPlant $record): ?string => $record->uploadedImageUrl())
                    ->square()
                    ->size(52)
                    ->checkFileExistence(false)
                    ->extraImgAttributes(['alt' => 'User uploaded scan image', 'loading' => 'lazy']),
                Tables\Columns\TextColumn::make('result_name')
                    ->label('Result (EN)')
                    ->getStateUsing(fn (ScannedPlant $record): string => $record->resultNames()['en'] ?? 'Not identified')
                    ->description(function (ScannedPlant $record): ?string {
                        $names = $record->resultNames();
                        $translations = array_filter([
                            filled($names['ru']) ? 'RU: '.$names['ru'] : null,
                            filled($names['uz']) ? 'UZ: '.$names['uz'] : null,
                        ]);

                        return $translations === [] ? null : implode(' · ', $translations);
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('result_scientific_name')
                    ->label('Scientific Name')
                    ->getStateUsing(fn (ScannedPlant $record): ?string => $record->resultScientificName())
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ScanStatus $state): string => $state->name)
                    ->color(fn (ScanStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('scan_mode')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str((string) $state)->replace('_', ' ')->title()->toString())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('User')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('similarity_score')
                    ->label('Score')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ai_cost_usd')
                    ->label('AI Cost')
                    ->formatStateUsing(fn (mixed $state): string => '$'.number_format((float) $state, 6))
                    ->sortable(),
                Tables\Columns\TextColumn::make('ai_provider_used')
                    ->label('Provider')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Scanned At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ScanStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->name])),
                Tables\Filters\SelectFilter::make('scan_mode')
                    ->options([
                        'plant_id' => 'Plant ID',
                        'ai_analysis' => 'AI Analysis',
                        'recognition' => 'Recognition',
                        'pests' => 'Pests',
                        'diagnosis' => 'Diagnosis',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'plantDetail', 'pestDetail']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canReplicate(Model $record): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }

    public static function canReorder(): bool
    {
        return false;
    }

    private static function formatJson(mixed $state): string
    {
        if ($state === null || $state === []) {
            return 'Not available';
        }

        if (! is_array($state)) {
            return (string) $state;
        }

        return json_encode(
            $state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        ) ?: 'Unable to display payload';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScannedPlants::route('/'),
            'view' => Pages\ViewScannedPlant::route('/{record}'),
        ];
    }
}
