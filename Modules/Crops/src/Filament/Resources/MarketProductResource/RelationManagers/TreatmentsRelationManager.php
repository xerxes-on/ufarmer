<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources\MarketProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\Disease;
use Modules\Crops\Models\Pest;
use Modules\Crops\Models\Treatment;
use Modules\Crops\Models\Weed;

class TreatmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'treatments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Problem Information')
                    ->schema([
                        Forms\Components\Select::make('problem_type')
                            ->label('Problem Type')
                            ->options([
                                'disease' => 'Disease',
                                'pest' => 'Pest',
                                'weed' => 'Weed',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('problem_id_poly', null)),

                        Forms\Components\Select::make('problem_id_poly')
                            ->label('Problem')
                            ->options(function (Forms\Get $get) {
                                $type = $get('problem_type');

                                if ($type === 'disease') {
                                    return Disease::query()
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn ($item) => [$item->id => $item->getTranslation('name', 'uz')])
                                        ->toArray();
                                }

                                if ($type === 'pest') {
                                    return Pest::query()
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn ($item) => [$item->id => $item->getTranslation('name', 'uz')])
                                        ->toArray();
                                }

                                if ($type === 'weed') {
                                    return Weed::query()
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn ($item) => [$item->id => $item->getTranslation('name', 'uz')])
                                        ->toArray();
                                }

                                return [];
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('problem_type') !== null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dosage')
                    ->schema([
                        Forms\Components\TextInput::make('dose_min')
                            ->label('Minimum Dose')
                            ->numeric()
                            ->step(0.001),
                        Forms\Components\TextInput::make('dose_max')
                            ->label('Maximum Dose')
                            ->numeric()
                            ->step(0.001),
                        Forms\Components\TextInput::make('dose_unit')
                            ->label('Unit')
                            ->placeholder('e.g., kg/ha, l/ha')
                            ->maxLength(50),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Instructions')
                    ->schema([
                        Forms\Components\Textarea::make('instructions')
                            ->label('Application Instructions')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Crops')
                    ->schema([
                        Forms\Components\Select::make('market_crops')
                            ->label('Associated Crops')
                            ->options(fn () => Crop::query()
                                ->select('crops.id')
                                ->selectRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') as name_label")
                                ->orderByRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en')")
                                ->limit(100)
                                ->pluck('name_label', 'id')
                                ->toArray())
                            ->getSearchResultsUsing(fn (string $search) => Crop::query()
                                ->select('crops.id')
                                ->selectRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') as name_label")
                                ->whereRaw(
                                    "COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') ILIKE ?",
                                    ["%{$search}%"]
                                )
                                ->orderByRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en')")
                                ->limit(50)
                                ->pluck('name_label', 'id')
                                ->toArray())
                            ->getOptionLabelsUsing(fn (array $values) => Crop::query()
                                ->select('crops.id')
                                ->selectRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') as name_label")
                                ->whereIn('crops.id', $values)
                                ->pluck('name_label', 'id')
                                ->toArray())
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->dehydrated(false)
                            ->loadStateFromRelationshipsUsing(function (Forms\Components\Select $component, Treatment $record): void {
                                $component->state(
                                    $record->cropsFromMarket()
                                        ->select('crops.id')
                                        ->pluck('crops.id')
                                        ->map(static fn ($id): string => (string) $id)
                                        ->all()
                                );
                            })
                            ->saveRelationshipsUsing(function (Forms\Components\Select $component, Treatment $record, ?array $state): void {
                                $ids = collect($state ?? [])
                                    ->map(static fn ($id): int => (int) $id)
                                    ->filter()
                                    ->values()
                                    ->all();

                                $record->cropsFromMarket()->sync($ids);
                            }),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('problem_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'disease' => 'danger',
                        'pest' => 'warning',
                        'weed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('problem')
                    ->label('Problem')
                    ->getStateUsing(function (Treatment $record): ?string {
                        $problem = $record->problem();

                        return $problem?->getTranslation('name', 'uz');
                    }),
                Tables\Columns\TextColumn::make('dose')
                    ->label('Dose')
                    ->getStateUsing(function (Treatment $record): ?string {
                        if ($record->dose_min !== null || $record->dose_max !== null) {
                            $min = $record->dose_min ?? '?';
                            $max = $record->dose_max ?? '?';
                            $unit = $record->dose_unit ?? '';

                            return "{$min} - {$max} {$unit}";
                        }

                        return null;
                    }),
                Tables\Columns\TextColumn::make('crops_count')
                    ->label('Crops')
                    ->getStateUsing(fn (Treatment $record) => $record->cropsFromMarket()->count())
                    ->badge()
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('problem_type')
                    ->label('Problem Type')
                    ->options([
                        'disease' => 'Disease',
                        'pest' => 'Pest',
                        'weed' => 'Weed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
