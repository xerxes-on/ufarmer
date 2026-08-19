<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use JsonException;
use Modules\AgroCalculator\Filament\Resources\ScoringThresholdResource\Pages;
use Modules\AgroCalculator\Models\ScoringThreshold;

class ScoringThresholdResource extends Resource
{
    protected static ?string $model = ScoringThreshold::class;

    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('agrocalculator::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_threshold.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_threshold.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_threshold.plural_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('scoring_model_id')
                ->label(__('agrocalculator::filament.resources.scoring_threshold.form.scoring_model_id'))
                ->relationship('scoringModel', 'name')
                ->required(),
            TextInput::make('metric_key')
                ->label(__('agrocalculator::filament.resources.scoring_threshold.form.metric_key'))
                ->default('score')
                ->required(),
            TextInput::make('min_value')
                ->label(__('agrocalculator::filament.resources.scoring_threshold.form.min_value'))
                ->numeric()
                ->nullable(),
            TextInput::make('max_value')
                ->label(__('agrocalculator::filament.resources.scoring_threshold.form.max_value'))
                ->numeric()
                ->nullable(),
            TextInput::make('label')
                ->label(__('agrocalculator::filament.resources.scoring_threshold.form.label'))
                ->required(),
            Textarea::make('meta')
                ->label(__('agrocalculator::filament.resources.scoring_threshold.form.meta'))
                ->rows(4)
                ->nullable()
                ->afterStateHydrated(function (Textarea $component, $state): void {
                    $component->state($state === null ? '' : json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                })
                ->dehydrateStateUsing(function ($state) {
                    if ($state === null || trim((string) $state) === '') {
                        return null;
                    }

                    try {
                        return json_decode((string) $state, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        throw ValidationException::withMessages([
                            'meta' => __('validation.json'),
                        ]);
                    }
                })
                ->rule('json'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scoringModel.name')
                    ->label(__('agrocalculator::filament.resources.scoring_threshold.table.columns.model'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('metric_key')
                    ->label(__('agrocalculator::filament.resources.scoring_threshold.table.columns.metric_key'))
                    ->sortable(),
                TextColumn::make('min_value')
                    ->label(__('agrocalculator::filament.resources.scoring_threshold.table.columns.min_value'))
                    ->sortable(),
                TextColumn::make('max_value')
                    ->label(__('agrocalculator::filament.resources.scoring_threshold.table.columns.max_value'))
                    ->sortable(),
                TextColumn::make('label')
                    ->label(__('agrocalculator::filament.resources.scoring_threshold.table.columns.label'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScoringThresholds::route('/'),
            'create' => Pages\CreateScoringThreshold::route('/create'),
            'edit' => Pages\EditScoringThreshold::route('/{record}/edit'),
        ];
    }
}
