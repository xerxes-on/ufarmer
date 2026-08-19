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
use Modules\AgroCalendar\Filament\Resources\LaborRateDefaultResource\Pages;
use Modules\AgroCalendar\Models\LaborRateDefault;

class LaborRateDefaultResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = LaborRateDefault::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Labor Rates';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Operation')
                    ->schema([
                        Forms\Components\TextInput::make('operation_type')
                            ->label('Operation Type')
                            ->required()
                            ->maxLength(64)
                            ->placeholder('e.g., planting, weeding, harvesting'),
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
                            ->placeholder('e.g., shovel, scissors'),
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
                        Forms\Components\TextInput::make('workers_per_ha')
                            ->label('Workers per Ha')
                            ->numeric()
                            ->step(0.1)
                            ->required(),
                        Forms\Components\TextInput::make('daily_rate')
                            ->label('Daily Rate (UZS)')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Hourly Rate (UZS)')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\TextInput::make('days_needed_per_ha')
                            ->label('Days Needed per Ha')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (LaborRateDefault $record): string => self::localized($record->getTranslations('name')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->getStateUsing(fn (LaborRateDefault $record): string => $record->region ? self::localized($record->region->getTranslations('name')) : 'Nationwide')
                    ->badge()
                    ->color(fn (LaborRateDefault $record): string => $record->region ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('workers_per_ha')
                    ->label('Workers/Ha')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_rate')
                    ->label('Daily Rate')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_needed_per_ha')
                    ->label('Days/Ha')
                    ->numeric(decimalPlaces: 2)
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
                    ->options(fn (): array => LaborRateDefault::query()
                        ->distinct()
                        ->pluck('operation_type', 'operation_type')
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
            'index' => Pages\ListLaborRateDefaults::route('/'),
            'create' => Pages\CreateLaborRateDefault::route('/create'),
            'edit' => Pages\EditLaborRateDefault::route('/{record}/edit'),
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
