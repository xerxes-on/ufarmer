<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AgroCalendar\Filament\Resources\CalendarRunResource;
use Modules\AgroCalendar\Models\AreaAnalysis;
use Modules\AgroCalendar\Models\CalendarRun;
use Modules\AgroCalendar\Models\IrrigationSourceType;
use Modules\AgroCalendar\Services\Analysis\CalendarSoilAnalysisResolver;
use Modules\Core\Enums\OwnershipType;
use Modules\Core\Filament\Infolists\Components\YandexMapEntry;
use Modules\Core\Filament\Resources\AreaResource\Pages;
use Modules\Core\Models\AppSetting;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;

class AreaResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Areas';

    protected static ?string $navigationGroup = NavigationGroup::Administration->value;

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Area Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('area')
                            ->label('Area (m²)')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(fn (): float => AppSetting::getValue('max_area_hectares', 100.0) * 10_000)
                            ->validationMessages([
                                'max' => fn (): string => 'Area may not be greater than '.(AppSetting::getValue('max_area_hectares', 100.0) * 10_000).' m².',
                            ]),
                        Forms\Components\Select::make('ownership_type')
                            ->label('Ownership Type')
                            ->options(OwnershipType::options())
                            ->default(OwnershipType::Owned->value),
                        Forms\Components\Toggle::make('irrigated')
                            ->label('Irrigated')
                            ->default(false)
                            ->reactive(),
                        Forms\Components\Select::make('water_source_type_id')
                            ->label('Water Source Type')
                            ->options(fn () => IrrigationSourceType::query()
                                ->active()
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn (IrrigationSourceType $type) => [
                                    $type->id => $type->getTranslation('name', app()->getLocale())
                                        .' ('.$type->efficiency_percentage.'%)',
                                ]))
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => $get('irrigated'))
                            ->helperText('Select the primary water source for irrigation'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Area Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('User'),
                        Infolists\Components\TextEntry::make('area')
                            ->label('Polygon Area')
                            ->suffix(' m²'),
                        Infolists\Components\TextEntry::make('calculated_area')
                            ->label('Calculated Area')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' ha'),
                        Infolists\Components\TextEntry::make('ownership_type')
                            ->label('Ownership')
                            ->badge(),
                        Infolists\Components\IconEntry::make('irrigated')
                            ->label('Irrigated')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('waterSourceType.name')
                            ->label('Water Source')
                            ->getStateUsing(fn (Area $record): string => $record->waterSourceType
                                ? $record->waterSourceType->getTranslation('name', app()->getLocale())
                                    .' ('.$record->waterSourceType->efficiency_percentage.'%)'
                                : '-'),
                        Infolists\Components\TextEntry::make('crops_count')
                            ->label('Active Crops')
                            ->state(fn (Area $record): int => $record->crops()->count())
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Soil Analysis')
                    ->description('Exact soil analysis resolved for the calendar-generation MQ payload.')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('calendar_soil_analysis')
                            ->label('')
                            ->getStateUsing(function (Area $record): array {
                                $analysis = app(CalendarSoilAnalysisResolver::class)->resolve($record);

                                return $analysis === null ? [] : [new AreaAnalysis($analysis)];
                            })
                            ->placeholder('No confirmed or default soil analysis can be resolved for this area.')
                            ->schema([
                                Infolists\Components\TextEntry::make('source')
                                    ->label('MQ Source')
                                    ->state(fn (AreaAnalysis $record): ?string => $record->source)
                                    ->badge(),
                                Infolists\Components\TextEntry::make('lab_name')
                                    ->label('Laboratory')
                                    ->state(fn (AreaAnalysis $record): ?string => $record->lab_name)
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('analysis_date')
                                    ->label('Analysis Date')
                                    ->state(fn (AreaAnalysis $record) => $record->analysis_date)
                                    ->date()
                                    ->placeholder('-'),
                                Infolists\Components\KeyValueEntry::make('details.soil_properties')
                                    ->label('Soil Properties')
                                    ->state(fn (AreaAnalysis $record): array => $record->details['soil_properties'] ?? [])
                                    ->placeholder('-'),
                                Infolists\Components\KeyValueEntry::make('details.macronutrients')
                                    ->label('Macronutrients')
                                    ->state(fn (AreaAnalysis $record): array => $record->details['macronutrients'] ?? [])
                                    ->placeholder('-'),
                                Infolists\Components\KeyValueEntry::make('details.micronutrients')
                                    ->label('Micronutrients')
                                    ->state(fn (AreaAnalysis $record): array => $record->details['micronutrients'] ?? [])
                                    ->placeholder('-'),
                                Infolists\Components\KeyValueEntry::make('details.carbonates')
                                    ->label('Carbonates')
                                    ->state(fn (AreaAnalysis $record): array => $record->details['carbonates'] ?? [])
                                    ->placeholder('-'),
                                Infolists\Components\KeyValueEntry::make('details.hydraulic_properties')
                                    ->label('Hydraulic Properties')
                                    ->state(fn (AreaAnalysis $record): array => $record->details['hydraulic_properties'] ?? [])
                                    ->placeholder('-'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Calendars')
                    ->description('Calendar runs linked to crops planted in this area.')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('calendar_runs')
                            ->label('')
                            ->getStateUsing(fn (Area $record) => CalendarRun::query()
                                ->with(['crop', 'areaCrop'])
                                ->whereHas('areaCrop', fn ($query) => $query->where('area_id', $record->id))
                                ->latest('created_at')
                                ->get())
                            ->placeholder('No calendar runs are linked to this area.')
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Calendar')
                                    ->url(fn (CalendarRun $record): string => CalendarRunResource::getUrl('view', ['record' => $record])),
                                Infolists\Components\TextEntry::make('crop.name')
                                    ->label('Crop')
                                    ->getStateUsing(fn (CalendarRun $record) => $record->crop?->getTranslation('name', app()->getLocale()) ?? '-'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : $state)
                                    ->badge(),
                                Infolists\Components\TextEntry::make('started_on')
                                    ->label('Started')
                                    ->date()
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('progress')
                                    ->label('Progress')
                                    ->suffix('%'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Updated')
                                    ->dateTime(),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Location Map')
                    ->schema([
                        YandexMapEntry::make('coordinates')
                            ->label('')
                            ->height(500)
                            ->zoom(15)
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Crops History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('areaCrops')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('crop.name')
                                    ->label('Crop'),
                                Infolists\Components\TextEntry::make('area')
                                    ->label('Area')
                                    ->suffix(' ha'),
                                Infolists\Components\TextEntry::make('date_started')
                                    ->label('Planted')
                                    ->date(),
                                Infolists\Components\TextEntry::make('expected_harvest_date')
                                    ->label('Expected Harvest')
                                    ->date()
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('harvested_at')
                                    ->label('Harvested')
                                    ->dateTime()
                                    ->placeholder('Not harvested'),
                                Infolists\Components\TextEntry::make('yield_amount')
                                    ->label('Yield')
                                    ->formatStateUsing(fn ($record) => $record->yield_amount
                                        ? "{$record->yield_amount} {$record->yield_unit}"
                                        : '-'),
                                Infolists\Components\IconEntry::make('active')
                                    ->label('Active')
                                    ->boolean(),
                            ])
                            ->columns(7)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Area Change History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('area_activity')
                            ->label('')
                            ->getStateUsing(fn (Area $record): Collection => self::areaActivity($record))
                            ->placeholder('No recorded area edits.')
                            ->schema([
                                Infolists\Components\TextEntry::make('changes')
                                    ->label('Changes')
                                    ->listWithLineBreaks()
                                    ->bulleted()
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('causer')
                                    ->label('Changed By'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Changed At')
                                    ->dateTime(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    private static function areaActivity(Area $record): Collection
    {
        $activities = DB::table('activity_log')
            ->where('subject_type', Area::class)
            ->where('subject_id', $record->id)
            ->where('event', 'updated')
            ->latest('id')
            ->limit(50)
            ->get();

        $users = User::query()
            ->whereIn('id', $activities->pluck('causer_id')->filter()->unique())
            ->pluck('name', 'id');

        return $activities->map(function ($activity) use ($users): array {
            $changesPayload = json_decode($activity->attribute_changes ?? '{}', true)
                ?: json_decode($activity->properties ?? '{}', true)
                ?: [];
            $old = $changesPayload['old'] ?? [];
            $attributes = $changesPayload['attributes'] ?? [];

            $changes = [];
            foreach ($attributes as $attribute => $value) {
                if (! array_key_exists($attribute, $old)) {
                    continue;
                }

                $changes[] = self::formatAreaChange($attribute, $old[$attribute], $value);
            }

            return [
                'changes' => $changes ?: [$activity->description],
                'causer' => $activity->causer_id
                    ? ($users[$activity->causer_id] ?? "User #{$activity->causer_id}")
                    : 'System',
                'created_at' => $activity->created_at,
            ];
        });
    }

    private static function formatAreaChange(string $attribute, mixed $old, mixed $new): string
    {
        $label = match ($attribute) {
            'area' => 'Area',
            'calculated_area' => 'Calculated area',
            'name' => 'Name',
            'coordinates' => 'Coordinates',
            'irrigated' => 'Irrigated',
            'ownership_type' => 'Ownership',
            'water_source_type_id' => 'Water source',
            'region_id' => 'Region',
            'area_type_id' => 'Area type',
            'user_id' => 'Owner',
            default => $attribute,
        };

        if ($attribute === 'coordinates') {
            return 'Coordinates updated';
        }

        $format = static function (mixed $value) use ($attribute): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($attribute === 'irrigated') {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
            }

            return match ($attribute) {
                'area' => "{$value} m²",
                'calculated_area' => "{$value} ha",
                default => (string) $value,
            };
        };

        return "{$label}: {$format($old)} → {$format($new)}";
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('area')
                    ->label('Area (m²)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('calculated_area')
                    ->label('Area (ha)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('ownership_type')
                    ->label('Ownership')
                    ->badge(),
                Tables\Columns\IconColumn::make('irrigated')
                    ->label('Irrigated')
                    ->boolean(),
                Tables\Columns\TextColumn::make('waterSourceType.name')
                    ->label('Water Source')
                    ->getStateUsing(fn (Area $record): string => $record->waterSourceType
                        ? $record->waterSourceType->getTranslation('name', app()->getLocale())
                        : '-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('crops_count')
                    ->label('Crops')
                    ->counts('crops')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ownership_type')
                    ->options(OwnershipType::options()),
                Tables\Filters\TernaryFilter::make('irrigated')
                    ->label('Irrigated')
                    ->boolean()
                    ->trueLabel('Irrigated only')
                    ->falseLabel('Non-irrigated only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'view' => Pages\ViewArea::route('/{record}'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
