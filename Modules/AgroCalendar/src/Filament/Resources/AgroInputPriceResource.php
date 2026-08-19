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
use Modules\AgroCalendar\Filament\Resources\AgroInputPriceResource\Pages;
use Modules\AgroCalendar\Models\AgroInputPrice;

class AgroInputPriceResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AgroInputPrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Input Prices';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('input_category_id')
                            ->label('Input Category ID')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('crop_id')
                            ->label('Crop (optional)')
                            ->relationship('crop', 'name->en')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for universal price'),
                        Forms\Components\Select::make('region_id')
                            ->label('Region (optional)')
                            ->relationship('region', 'name->en')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for nationwide price'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Name & Pricing')
                    ->schema([
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
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->required()
                            ->maxLength(32)
                            ->placeholder('e.g., kg, L, pcs'),
                        Forms\Components\TextInput::make('price_per_unit')
                            ->label('Price per Unit (UZS)')
                            ->numeric()
                            ->required()
                            ->step(0.01),
                        Forms\Components\TextInput::make('season_year')
                            ->label('Season Year')
                            ->numeric()
                            ->placeholder('e.g., 2026'),
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
                Tables\Columns\TextColumn::make('input_category_id')
                    ->label('Category ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (AgroInputPrice $record): string => self::localized($record->getTranslations('name')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('crop.name')
                    ->label('Crop')
                    ->getStateUsing(fn (AgroInputPrice $record): string => $record->crop ? self::localized($record->crop->getTranslations('name')) : 'All')
                    ->badge()
                    ->color(fn (AgroInputPrice $record): string => $record->crop ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->getStateUsing(fn (AgroInputPrice $record): string => $record->region ? self::localized($record->region->getTranslations('name')) : 'Nationwide')
                    ->badge()
                    ->color(fn (AgroInputPrice $record): string => $record->region ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit'),
                Tables\Columns\TextColumn::make('price_per_unit')
                    ->label('Price/Unit')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('season_year')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('input_category_id')
                    ->label('Category')
                    ->options(fn (): array => AgroInputPrice::query()
                        ->whereNotNull('input_category_id')
                        ->distinct()
                        ->orderBy('input_category_id')
                        ->pluck('input_category_id', 'input_category_id')
                        ->all()),
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Crop')
                    ->relationship('crop', 'name->en')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('Region')
                    ->relationship('region', 'name->en')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgroInputPrices::route('/'),
            'create' => Pages\CreateAgroInputPrice::route('/create'),
            'edit' => Pages\EditAgroInputPrice::route('/{record}/edit'),
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
