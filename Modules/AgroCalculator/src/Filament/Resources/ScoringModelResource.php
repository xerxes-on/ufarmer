<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use JsonException;
use Modules\AgroCalculator\Filament\Resources\ScoringModelResource\Pages;
use Modules\AgroCalculator\Models\ScoringModel;

class ScoringModelResource extends Resource
{
    protected static ?string $model = ScoringModel::class;

    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('agrocalculator::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_model.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_model.plural_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    private const ACTIVE_STATE = true;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->label(__('agrocalculator::filament.resources.scoring_model.form.name'))
                    ->required(),
                TextInput::make('code')
                    ->label(__('agrocalculator::filament.resources.scoring_model.form.code'))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('scope')
                    ->label(__('agrocalculator::filament.resources.scoring_model.form.scope'))
                    ->nullable(),
                TextInput::make('version')
                    ->label(__('agrocalculator::filament.resources.scoring_model.form.version'))
                    ->numeric()
                    ->default(static function (): int {
                        $max = ScoringModel::query()->max('version');

                        return $max !== null ? $max + 1 : 1;
                    })
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('agrocalculator::filament.resources.scoring_model.form.is_active'))
                    ->default(static fn (): bool => self::ACTIVE_STATE),
                Forms\Components\DatePicker::make('valid_from')
                    ->label(__('agrocalculator::filament.resources.scoring_model.form.valid_from')),
                Forms\Components\DatePicker::make('valid_to')
                    ->label(__('agrocalculator::filament.resources.scoring_model.form.valid_to')),
            ]),
            Textarea::make('spec')
                ->label(__('agrocalculator::filament.resources.scoring_model.form.spec'))
                ->rows(16)
                ->required()
                ->afterStateHydrated(function (Textarea $component, $state): void {
                    $component->state(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                })
                ->dehydrateStateUsing(function ($state) {
                    if ($state === null || $state === '') {
                        return [];
                    }

                    try {
                        return json_decode((string) $state, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        throw ValidationException::withMessages([
                            'spec' => __('validation.json'),
                        ]);
                    }
                })
                ->rule('json'),
            Textarea::make('meta')
                ->label(__('agrocalculator::filament.resources.scoring_model.form.meta'))
                ->rows(6)
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
                ->rule('json')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('agrocalculator::filament.resources.scoring_model.table.columns.name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('code')
                    ->label(__('agrocalculator::filament.resources.scoring_model.table.columns.code'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('version')
                    ->label(__('agrocalculator::filament.resources.scoring_model.table.columns.version'))
                    ->sortable(),
                BadgeColumn::make('is_active')
                    ->label(__('agrocalculator::filament.resources.scoring_model.table.columns.is_active'))
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                TextColumn::make('valid_from')
                    ->label(__('agrocalculator::filament.resources.scoring_model.table.columns.valid_from'))
                    ->date(),
                TextColumn::make('valid_to')
                    ->label(__('agrocalculator::filament.resources.scoring_model.table.columns.valid_to'))
                    ->date(),
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
            'index' => Pages\ListScoringModels::route('/'),
            'create' => Pages\CreateScoringModel::route('/create'),
            'edit' => Pages\EditScoringModel::route('/{record}/edit'),
        ];
    }
}
