<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use JsonException;
use Modules\AgroCalculator\Filament\Resources\CropParameterSetResource\Pages;
use Modules\AgroCalculator\Models\CropParameterSet;

class CropParameterSetResource extends Resource
{
    protected static ?string $model = CropParameterSet::class;

    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('agrocalculator::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('agrocalculator::filament.resources.crop_parameter_set.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('agrocalculator::filament.resources.crop_parameter_set.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('agrocalculator::filament.resources.crop_parameter_set.plural_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    private const ACTIVE_STATE = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    Select::make('crop_id')
                        ->label(__('agrocalculator::filament.resources.crop_parameter_set.form.crop_id'))
                        ->relationship('crop', 'name')
                        ->required(),
                    TextInput::make('version')
                        ->label(__('agrocalculator::filament.resources.crop_parameter_set.form.version'))
                        ->numeric()
                        ->default(static function (): int {
                            $max = CropParameterSet::query()->max('version');

                            return $max !== null ? $max + 1 : 1;
                        })
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('agrocalculator::filament.resources.crop_parameter_set.form.is_active'))
                        ->default(static fn (): bool => self::ACTIVE_STATE)
                        ->required(),
                    DatePicker::make('valid_from')
                        ->label(__('agrocalculator::filament.resources.crop_parameter_set.form.valid_from')),
                    DatePicker::make('valid_to')
                        ->label(__('agrocalculator::filament.resources.crop_parameter_set.form.valid_to')),
                ]),
                Textarea::make('params')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.form.params'))
                    ->rows(18)
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
                                'params' => __('validation.json'),
                            ]);
                        }
                    })
                    ->rule('json'),
                Textarea::make('meta')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.form.meta'))
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
                TextColumn::make('id')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.table.columns.id'))
                    ->sortable(),
                TextColumn::make('crop.name')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.table.columns.crop'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('version')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.table.columns.version'))
                    ->sortable(),
                BadgeColumn::make('is_active')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.table.columns.is_active'))
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                TextColumn::make('valid_from')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.table.columns.valid_from'))
                    ->date(),
                TextColumn::make('valid_to')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.table.columns.valid_to'))
                    ->date(),
                TextColumn::make('created_at')
                    ->label(__('agrocalculator::filament.resources.crop_parameter_set.table.columns.created'))
                    ->dateTime(),
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
            'index' => Pages\ListCropParameterSets::route('/'),
            'create' => Pages\CreateCropParameterSet::route('/create'),
            'edit' => Pages\EditCropParameterSet::route('/{record}/edit'),
        ];
    }
}
