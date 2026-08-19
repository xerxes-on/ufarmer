<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\AgroCalendar\Filament\Resources\MachineryRateDefaultResource\Pages;
use Modules\AgroCalendar\Models\MachineryRateDefault;

class MachineryRateDefaultResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = MachineryRateDefault::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Machinery Rates';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Operation & Type')
                    ->schema([
                        Forms\Components\TextInput::make('operation_type')
                            ->label('Operation Type')
                            ->required()
                            ->maxLength(64)
                            ->placeholder('e.g., tillage, planting, spraying'),
                        Forms\Components\TextInput::make('machinery_type')
                            ->label('Machinery Type')
                            ->required()
                            ->maxLength(64)
                            ->placeholder('e.g., tractor, combine, sprayer'),
                        Forms\Components\TextInput::make('name.uz')
                            ->label('Name (Uzbek)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name.ru')
                            ->label('Name (Russian)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name.en')
                            ->label('Name (English)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('icon')
                            ->maxLength(64)
                            ->placeholder('e.g., tractor, combine'),
                        Forms\Components\Select::make('region_id')
                            ->label('Region (optional)')
                            ->relationship('region', 'name->en')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for nationwide rate'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Rates')
                    ->schema([
                        Forms\Components\TextInput::make('rental_rate_per_ha')
                            ->label('Rental Rate per Ha (UZS)')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('fuel_liters_per_ha')
                            ->label('Fuel (L/Ha)')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\TextInput::make('default_fuel_price')
                            ->label('Default Fuel Price (UZS/L)')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\TextInput::make('sessions_default')
                            ->label('Default Sessions')
                            ->numeric()
                            ->default(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('operation_type')
                    ->label('Operation')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('machinery_type')
                    ->label('Machinery')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (MachineryRateDefault $record): string => self::localized($record->getTranslations('name')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->getStateUsing(fn (MachineryRateDefault $record): string => $record->region ? self::localized($record->region->getTranslations('name')) : 'Nationwide')
                    ->badge()
                    ->color(fn (MachineryRateDefault $record): string => $record->region ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('rental_rate_per_ha')
                    ->label('Rental/Ha')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('fuel_liters_per_ha')
                    ->label('Fuel L/Ha')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('sessions_default')
                    ->label('Sessions')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('Region')
                    ->relationship('region', 'name->en')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('operation_type')
                    ->label('Operation')
                    ->options(fn (): array => MachineryRateDefault::query()
                        ->distinct()
                        ->pluck('operation_type', 'operation_type')
                        ->all()),
                Tables\Filters\SelectFilter::make('machinery_type')
                    ->label('Machinery')
                    ->options(fn (): array => MachineryRateDefault::query()
                        ->distinct()
                        ->pluck('machinery_type', 'machinery_type')
                        ->all()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('operation_type');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMachineryRateDefaults::route('/'),
            'create' => Pages\CreateMachineryRateDefault::route('/create'),
            'edit' => Pages\EditMachineryRateDefault::route('/{record}/edit'),
        ];
    }

    private static function localized(array|string|null $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '-';
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($value[$locale] ?? $value[$fallback] ?? $value['uz'] ?? reset($value) ?: '-');
    }
}
