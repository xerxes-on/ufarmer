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
use Modules\AgroCalendar\Filament\Resources\CropYieldEstimateResource\Pages;
use Modules\AgroCalendar\Models\CropYieldEstimate;

class CropYieldEstimateResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = CropYieldEstimate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Crop Yield Estimates';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 70;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Crop & Location')
                    ->schema([
                        Forms\Components\Select::make('crop_id')
                            ->label('Crop')
                            ->relationship('crop', 'name->en')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('region_id')
                            ->label('Region (optional)')
                            ->relationship('region', 'name->en')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for nationwide estimate'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Yield Data')
                    ->schema([
                        Forms\Components\TextInput::make('yield_per_ha_min')
                            ->label('Min Yield (t/ha)')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('yield_per_ha_avg')
                            ->label('Avg Yield (t/ha)')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('yield_per_ha_max')
                            ->label('Max Yield (t/ha)')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unit')
                            ->required()
                            ->maxLength(16)
                            ->default('ton')
                            ->placeholder('e.g., ton, kg'),
                        Forms\Components\TextInput::make('source')
                            ->label('Data Source')
                            ->maxLength(128)
                            ->placeholder('e.g., FAO, local stats'),
                        Forms\Components\TextInput::make('year')
                            ->label('Year')
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
                Tables\Columns\TextColumn::make('crop.name')
                    ->label('Crop')
                    ->getStateUsing(fn (CropYieldEstimate $record): string => $record->crop ? self::localized($record->crop->getTranslations('name')) : '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('crop', function (Builder $q) use ($search) {
                            $q->where('name->uz', 'like', "%{$search}%")
                                ->orWhere('name->en', 'like', "%{$search}%")
                                ->orWhere('name->ru', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Region')
                    ->getStateUsing(fn (CropYieldEstimate $record): string => $record->region ? self::localized($record->region->getTranslations('name')) : 'Nationwide')
                    ->badge()
                    ->color(fn (CropYieldEstimate $record): string => $record->region ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('yield_per_ha_min')
                    ->label('Min')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' t/ha'),
                Tables\Columns\TextColumn::make('yield_per_ha_avg')
                    ->label('Avg')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' t/ha')
                    ->sortable(),
                Tables\Columns\TextColumn::make('yield_per_ha_max')
                    ->label('Max')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' t/ha'),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
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
            ->defaultSort('crop_id');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropYieldEstimates::route('/'),
            'create' => Pages\CreateCropYieldEstimate::route('/create'),
            'edit' => Pages\EditCropYieldEstimate::route('/{record}/edit'),
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
