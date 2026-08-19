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
use Modules\AgroCalendar\Enums\SoilFinishingStatus;
use Modules\AgroCalendar\Filament\Resources\FieldHistoryResource\Pages;
use Modules\AgroCalendar\Models\AgroUnit;
use Modules\AgroCalendar\Models\FieldHistory;

class FieldHistoryResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = FieldHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Field Histories';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 110;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Field & Crop')
                    ->schema([
                        Forms\Components\Select::make('area_id')
                            ->label('Area')
                            ->relationship('area', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('crop_id')
                            ->label('Crop')
                            ->relationship('crop', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->localized_name ?? $record->id)
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('season_year')
                            ->label('Season Year')
                            ->numeric()
                            ->default(date('Y'))
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Harvest Information')
                    ->schema([
                        Forms\Components\DatePicker::make('harvest_date')
                            ->label('Harvest Date'),
                        Forms\Components\TextInput::make('actual_yield')
                            ->label('Actual Yield')
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\Select::make('yield_unit_id')
                            ->label('Yield Unit')
                            ->options(fn (): array => AgroUnit::yieldUnitOptions())
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Post-Harvest Soil Status')
                    ->schema([
                        Forms\Components\Select::make('soil_finishing_status')
                            ->label('Soil Finishing Status')
                            ->options(fn (): array => SoilFinishingStatus::options())
                            ->helperText('What was done with the soil after harvest'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable(),
                Tables\Columns\TextColumn::make('crop.name')
                    ->label('Crop')
                    ->formatStateUsing(fn (FieldHistory $record): string => $record->crop?->localized_name ?? '-'),
                Tables\Columns\TextColumn::make('season_year')
                    ->label('Season')
                    ->sortable(),
                Tables\Columns\TextColumn::make('harvest_date')
                    ->label('Harvested')
                    ->date(),
                Tables\Columns\TextColumn::make('actual_yield')
                    ->label('Yield')
                    ->formatStateUsing(fn (FieldHistory $record): string => $record->getFormattedYield() ?? '-'),
                Tables\Columns\TextColumn::make('soil_finishing_status')
                    ->label('Soil Status')
                    ->formatStateUsing(fn (FieldHistory $record): string => $record->soil_finishing_status?->label()['en'] ?? '-')
                    ->badge()
                    ->color(fn (?SoilFinishingStatus $state): string => match ($state) {
                        SoilFinishingStatus::LEFT_AS_IS => 'gray',
                        SoilFinishingStatus::PLOWED, SoilFinishingStatus::FERTILIZED => 'success',
                        SoilFinishingStatus::COVER_CROP, SoilFinishingStatus::MULCHED => 'info',
                        SoilFinishingStatus::PREPARED_FOR_NEXT => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Crop')
                    ->relationship('crop', 'id')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('season_year')
                    ->label('Season')
                    ->options(fn (): array => FieldHistory::query()
                        ->select('season_year')
                        ->distinct()
                        ->orderByDesc('season_year')
                        ->pluck('season_year', 'season_year')
                        ->map(fn ($value) => (string) $value)
                        ->all()),
                Tables\Filters\SelectFilter::make('soil_finishing_status')
                    ->label('Soil Status')
                    ->options(fn (): array => SoilFinishingStatus::options()),
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
            ->defaultSort('season_year', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFieldHistories::route('/'),
            'create' => Pages\CreateFieldHistory::route('/create'),
            'edit' => Pages\EditFieldHistory::route('/{record}/edit'),
        ];
    }
}
