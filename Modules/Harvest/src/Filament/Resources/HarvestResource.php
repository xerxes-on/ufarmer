<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Harvest\Filament\Resources\HarvestResource\Pages;
use Modules\Harvest\Filament\Resources\HarvestResource\RelationManagers;
use Modules\Harvest\Models\Harvest;
use Modules\Harvest\Models\HarvestGrowthType;
use Modules\Harvest\Models\HarvestPriceType;
use Modules\Harvest\Models\HarvestQualityGrade;

class HarvestResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Harvest::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationLabel = 'Harvests';

    protected static ?string $navigationGroup = NavigationGroup::MarketplaceAndPrices->value;

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Harvest';

    protected static ?string $pluralModelLabel = 'Harvests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Crop & Area Information')
                    ->schema([
                        Forms\Components\Select::make('area_crop_id')
                            ->label('Area Crop')
                            ->relationship('areaCrop', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->crop?->getTranslation('name', 'uz')} in {$record->area?->name}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('crop_id')
                            ->label('Crop')
                            ->relationship('crop', 'name->uz')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('area_id')
                            ->label('Area')
                            ->relationship('area', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DatePicker::make('scheduled_harvest_date')
                            ->label('Scheduled Harvest Date'),

                        Forms\Components\Select::make('calendar_run_id')
                            ->label('Calendar Run')
                            ->relationship('calendarRun', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Run #{$record->id}")
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Estimation')
                    ->schema([
                        Forms\Components\TextInput::make('estimated_amount')
                            ->label('Estimated Amount')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\Select::make('estimated_unit_id')
                            ->label('Estimated Unit')
                            ->relationship('estimatedUnit', 'name->uz')
                            ->searchable()
                            ->preload(),

                        Forms\Components\DatePicker::make('estimated_harvest_date')
                            ->label('Estimated Harvest Date'),

                        Forms\Components\DateTimePicker::make('estimated_at')
                            ->label('Estimated At'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Actual Harvest Data')
                    ->schema([
                        Forms\Components\TextInput::make('actual_amount')
                            ->label('Actual Amount')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\Select::make('actual_unit_id')
                            ->label('Actual Unit')
                            ->relationship('actualUnit', 'name->uz')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('area_harvested')
                            ->label('Area Harvested (sqm)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('yield_per_hectare')
                            ->label('Yield per Hectare')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('harvested_at')
                            ->label('Harvested At'),

                        Forms\Components\Toggle::make('is_early_harvest')
                            ->label('Early Harvest')
                            ->default(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Quality')
                    ->schema([
                        Forms\Components\Select::make('quality_grade_id')
                            ->label('Quality Grade')
                            ->options(HarvestQualityGrade::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($grade) => [$grade->id => $grade->getTranslation('name', 'uz')]))
                            ->searchable(),

                        Forms\Components\TextInput::make('quality_score')
                            ->label('Quality Score (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\KeyValue::make('quality_attributes')
                            ->label('Quality Attributes'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Weather & Growth')
                    ->schema([
                        Forms\Components\Select::make('growth_type_id')
                            ->label('Growth Type')
                            ->options(HarvestGrowthType::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($type) => [$type->id => $type->getTranslation('name', 'uz')]))
                            ->searchable(),

                        Forms\Components\TextInput::make('temperature_celsius')
                            ->label('Temperature (°C)')
                            ->numeric(),

                        Forms\Components\TextInput::make('humidity_percent')
                            ->label('Humidity (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),

                        Forms\Components\KeyValue::make('weather_data')
                            ->label('Additional Weather Data'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Selling Price')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\Select::make('price_type_id')
                            ->label('Price Type')
                            ->options(HarvestPriceType::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($type) => [$type->id => $type->getTranslation('name', 'uz')]))
                            ->searchable(),

                        Forms\Components\Select::make('price_unit_id')
                            ->label('Price Unit')
                            ->relationship('priceUnit', 'name->uz')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('currency_id')
                            ->label('Currency')
                            ->relationship('currency', 'name->uz')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('total_value')
                            ->label('Total Value')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Timing')
                    ->schema([
                        Forms\Components\TextInput::make('days_from_planting')
                            ->label('Days from Planting')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('days_variance')
                            ->label('Days Variance')
                            ->numeric()
                            ->disabled()
                            ->helperText('Positive = late, Negative = early'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('crop.name')
                    ->label('Crop')
                    ->getStateUsing(fn (Harvest $record) => $record->crop?->getTranslation('name', 'uz'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('crop', function (Builder $q) use ($search): void {
                            $q->where('name->uz', 'like', "%{$search}%")
                                ->orWhere('name->en', 'like', "%{$search}%")
                                ->orWhere('name->ru', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('scheduled_harvest_date')
                    ->label('Scheduled')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('harvested_at')
                    ->label('Harvested')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_amount')
                    ->label('Actual')
                    ->formatStateUsing(fn (Harvest $record) => $record->actual_amount
                        ? "{$record->actual_amount} {$record->actualUnit?->code}"
                        : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_amount')
                    ->label('Estimated')
                    ->formatStateUsing(fn (Harvest $record) => $record->estimated_amount
                        ? "{$record->estimated_amount} {$record->estimatedUnit?->code}"
                        : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('qualityGrade.name')
                    ->label('Quality')
                    ->getStateUsing(fn (Harvest $record) => $record->qualityGrade?->getTranslation('name', 'uz'))
                    ->badge()
                    ->color(fn (Harvest $record) => match ($record->qualityGrade?->code) {
                        'premium' => 'success',
                        'standard' => 'info',
                        'economy' => 'warning',
                        'substandard' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_early_harvest')
                    ->label('Early')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Value')
                    ->formatStateUsing(fn (Harvest $record) => $record->total_value
                        ? number_format((float) $record->total_value, 2).' '.($record->currency?->code ?? '')
                        : '-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('quality_grade_id')
                    ->label('Quality Grade')
                    ->options(HarvestQualityGrade::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn ($grade) => [$grade->id => $grade->getTranslation('name', 'uz')])),

                Tables\Filters\TernaryFilter::make('harvested')
                    ->label('Harvest Status')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('harvested_at'),
                        false: fn (Builder $query) => $query->whereNull('harvested_at'),
                    )
                    ->trueLabel('Harvested')
                    ->falseLabel('Pending'),

                Tables\Filters\TernaryFilter::make('is_early_harvest')
                    ->label('Early Harvest')
                    ->boolean(),

                Tables\Filters\Filter::make('harvested_at')
                    ->form([
                        Forms\Components\DatePicker::make('harvested_from')
                            ->label('Harvested From'),
                        Forms\Components\DatePicker::make('harvested_until')
                            ->label('Harvested Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['harvested_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('harvested_at', '>=', $date),
                            )
                            ->when(
                                $data['harvested_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('harvested_at', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Crop & Area')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('User'),
                        Infolists\Components\TextEntry::make('crop.name')
                            ->label('Crop')
                            ->getStateUsing(fn (Harvest $record) => $record->crop?->getTranslation('name', 'uz')),
                        Infolists\Components\TextEntry::make('area.name')
                            ->label('Area'),
                        Infolists\Components\TextEntry::make('areaCrop.id')
                            ->label('Area Crop ID'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Harvest Data')
                    ->schema([
                        Infolists\Components\TextEntry::make('scheduled_harvest_date')
                            ->label('Scheduled Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('harvested_at')
                            ->label('Harvested At')
                            ->dateTime(),
                        Infolists\Components\IconEntry::make('is_early_harvest')
                            ->label('Early Harvest')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('days_variance')
                            ->label('Days Variance')
                            ->suffix(' days'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Estimation vs Actual')
                    ->schema([
                        Infolists\Components\TextEntry::make('estimated_amount')
                            ->label('Estimated Amount')
                            ->formatStateUsing(fn (Harvest $record) => $record->estimated_amount
                                ? "{$record->estimated_amount} {$record->estimatedUnit?->code}"
                                : '-'),
                        Infolists\Components\TextEntry::make('actual_amount')
                            ->label('Actual Amount')
                            ->formatStateUsing(fn (Harvest $record) => $record->actual_amount
                                ? "{$record->actual_amount} {$record->actualUnit?->code}"
                                : '-'),
                        Infolists\Components\TextEntry::make('area_harvested')
                            ->label('Area Harvested')
                            ->suffix(' sqm'),
                        Infolists\Components\TextEntry::make('yield_per_hectare')
                            ->label('Yield per Hectare'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Quality & Weather')
                    ->schema([
                        Infolists\Components\TextEntry::make('qualityGrade.name')
                            ->label('Quality Grade')
                            ->getStateUsing(fn (Harvest $record) => $record->qualityGrade?->getTranslation('name', 'uz'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('quality_score')
                            ->label('Quality Score'),
                        Infolists\Components\TextEntry::make('temperature_celsius')
                            ->label('Temperature')
                            ->suffix('°C'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('selling_price')
                            ->label('Selling Price')
                            ->formatStateUsing(fn (Harvest $record) => $record->selling_price
                                ? number_format((float) $record->selling_price, 2).' / '.($record->priceUnit?->code ?? '')
                                : '-'),
                        Infolists\Components\TextEntry::make('total_value')
                            ->label('Total Value')
                            ->formatStateUsing(fn (Harvest $record) => $record->total_value
                                ? number_format((float) $record->total_value, 2).' '.($record->currency?->code ?? '')
                                : '-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHarvests::route('/'),
            'create' => Pages\CreateHarvest::route('/create'),
            'view' => Pages\ViewHarvest::route('/{record}'),
            'edit' => Pages\EditHarvest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}
