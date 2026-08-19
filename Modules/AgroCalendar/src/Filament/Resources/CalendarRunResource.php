<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\AgroCalendar\Enums\CalendarRunStatus;
use Modules\AgroCalendar\Filament\Resources\CalendarRunResource\Pages;
use Modules\AgroCalendar\Models\CalendarRun;

class CalendarRunResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = CalendarRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-play';

    protected static ?string $navigationLabel = 'Calendar Runs';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('template_id')
                    ->label('Template')
                    ->disabled(),
                Forms\Components\TextInput::make('crop_id')
                    ->label('Crop')
                    ->disabled(),
                Forms\Components\TextInput::make('user_id')
                    ->label('User')
                    ->disabled(),
                Forms\Components\DatePicker::make('started_on')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
                Forms\Components\TextInput::make('progress')
                    ->disabled(),
                Forms\Components\TextInput::make('accuracy_pct')
                    ->disabled(),
                Forms\Components\TextInput::make('farming_goal')
                    ->disabled(),
                Forms\Components\KeyValue::make('metadata')
                    ->disabled()
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('crop.name')
                    ->label('Crop')
                    ->formatStateUsing(fn (CalendarRun $record): string => $record->crop?->localized_name ?? '-'),
                Tables\Columns\TextColumn::make('user.auth_id')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('started_on')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('accuracy_pct')
                    ->label('Accuracy %')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('farming_goal')
                    ->label('Farming Goal')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'auth_id')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Crop')
                    ->relationship('crop', 'id')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(self::statusOptions()),
                Tables\Filters\Filter::make('started_on')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('started_on', '>=', $date),
                            )
                            ->when(
                                $data['to_date'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('started_on', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('farming_goal')
                    ->options(fn (): array => CalendarRun::query()
                        ->whereNotNull('farming_goal')
                        ->distinct()
                        ->pluck('farming_goal', 'farming_goal')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('updateStatus')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options(self::statusOptions())
                            ->required(),
                    ])
                    ->action(function (CalendarRun $record, array $data): void {
                        $record->update(['status' => $data['status']]);

                        Notification::make()
                            ->title('Status updated successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('viewMetadata')
                    ->icon('heroicon-o-code-bracket')
                    ->color('gray')
                    ->fillForm(fn (CalendarRun $record): array => [
                        'metadata' => json_encode($record->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    ])
                    ->form([
                        Forms\Components\Textarea::make('metadata')
                            ->rows(18)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->action(static function (): void {}),
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Overview Section
                Infolists\Components\Section::make('Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Run ID')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Farmer')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('crop.name')
                            ->label('Crop')
                            ->getStateUsing(fn (CalendarRun $record) => $record->crop?->localized_name ?? '-')
                            ->icon('heroicon-o-leaf'),
                        Infolists\Components\TextEntry::make('areaCrop.area.name')
                            ->label('Area')
                            ->icon('heroicon-o-map'),
                        Infolists\Components\TextEntry::make('started_on')
                            ->label('Started On')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (CalendarRun $record) => match ($record->status) {
                                CalendarRunStatus::COMPLETED => 'success',
                                CalendarRunStatus::PROCESSING, CalendarRunStatus::PENDING => 'warning',
                                CalendarRunStatus::FAILED => 'danger',
                                CalendarRunStatus::COMPLETED_WITH_WARNINGS => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('progress')
                            ->label('Progress')
                            ->suffix('%')
                            ->badge()
                            ->color(fn (CalendarRun $record) => match (true) {
                                $record->progress >= 80 => 'success',
                                $record->progress >= 50 => 'info',
                                $record->progress >= 20 => 'warning',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('farming_goal')
                            ->label('Farming Goal')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('accuracy_pct')
                            ->label('Accuracy')
                            ->suffix('%')
                            ->badge()
                            ->color(fn (?float $state) => match (true) {
                                $state === null => 'gray',
                                $state >= 90 => 'success',
                                $state >= 75 => 'info',
                                $state >= 50 => 'warning',
                                default => 'danger',
                            }),
                    ])
                    ->columns(4),

                // GDD & Growth Stage Section
                Infolists\Components\Section::make('GDD & Growth Stage')
                    ->schema([
                        Infolists\Components\TextEntry::make('current_bbch_stage')
                            ->label('Current BBCH Stage')
                            ->badge()
                            ->color('primary')
                            ->placeholder('Not set'),
                        Infolists\Components\TextEntry::make('stage_confidence')
                            ->label('Stage Confidence')
                            ->suffix('%')
                            ->badge()
                            ->color(fn (?float $state) => match (true) {
                                $state === null => 'gray',
                                $state >= 80 => 'success',
                                $state >= 60 => 'info',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('stage_updated_at')
                            ->label('Stage Updated')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('metadata.gdd_accumulated')
                            ->label('GDD Accumulated')
                            ->getStateUsing(fn (CalendarRun $record) => $record->metadata['gdd_accumulated'] ?? null)
                            ->numeric(decimalPlaces: 1)
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('metadata.gdd_target')
                            ->label('GDD Target')
                            ->getStateUsing(fn (CalendarRun $record) => $record->metadata['gdd_target'] ?? null)
                            ->numeric(decimalPlaces: 1)
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('gdd_progress')
                            ->label('GDD Progress')
                            ->getStateUsing(function (CalendarRun $record) {
                                $accumulated = $record->metadata['gdd_accumulated'] ?? 0;
                                $target = $record->metadata['gdd_target'] ?? 0;
                                if ($target <= 0) {
                                    return null;
                                }

                                return round(($accumulated / $target) * 100, 1);
                            })
                            ->suffix('%')
                            ->badge()
                            ->color(fn (?float $state) => match (true) {
                                $state === null => 'gray',
                                $state >= 100 => 'success',
                                $state >= 75 => 'info',
                                $state >= 50 => 'warning',
                                default => 'danger',
                            }),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Predictions Section
                Infolists\Components\Section::make('Harvest & Yield Predictions')
                    ->schema([
                        Infolists\Components\TextEntry::make('predicted_harvest_date')
                            ->label('Predicted Harvest')
                            ->date()
                            ->icon('heroicon-o-calendar'),
                        Infolists\Components\TextEntry::make('days_until_harvest')
                            ->label('Days Until Harvest')
                            ->getStateUsing(fn (CalendarRun $record) => $record->getDaysUntilHarvest())
                            ->suffix(' days')
                            ->badge()
                            ->color(fn (?int $state) => match (true) {
                                $state === null => 'gray',
                                $state <= 0 => 'danger',
                                $state <= 7 => 'warning',
                                $state <= 30 => 'info',
                                default => 'success',
                            }),
                        Infolists\Components\TextEntry::make('predicted_yield')
                            ->label('Predicted Yield')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' t/ha'),
                        Infolists\Components\TextEntry::make('yield_confidence')
                            ->label('Yield Confidence')
                            ->suffix('%')
                            ->badge()
                            ->color(fn (?float $state) => match (true) {
                                $state === null => 'gray',
                                $state >= 80 => 'success',
                                $state >= 60 => 'info',
                                $state >= 40 => 'warning',
                                default => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('actual_harvest_date')
                            ->label('Actual Harvest')
                            ->date()
                            ->placeholder('Not harvested yet'),
                        Infolists\Components\TextEntry::make('actual_yield')
                            ->label('Actual Yield')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' t/ha')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Soil Data Section
                Infolists\Components\Section::make('Soil Data')
                    ->schema([
                        Infolists\Components\TextEntry::make('soil_ph')
                            ->label('pH')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->ph;
                            })
                            ->badge()
                            ->color(fn (?float $state) => match (true) {
                                $state === null => 'gray',
                                $state >= 6.0 && $state <= 7.0 => 'success',
                                $state >= 5.5 && $state <= 7.5 => 'info',
                                default => 'warning',
                            }),
                        Infolists\Components\TextEntry::make('soil_nitrogen')
                            ->label('Nitrogen (ppm)')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->nitrogen_ppm;
                            })
                            ->numeric(decimalPlaces: 1),
                        Infolists\Components\TextEntry::make('soil_phosphorus')
                            ->label('Phosphorus (ppm)')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->phosphorus_ppm;
                            })
                            ->numeric(decimalPlaces: 1),
                        Infolists\Components\TextEntry::make('soil_potassium')
                            ->label('Potassium (ppm)')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->potassium_ppm;
                            })
                            ->numeric(decimalPlaces: 1),
                        Infolists\Components\TextEntry::make('soil_organic_matter')
                            ->label('Organic Matter')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->organic_matter_pct;
                            })
                            ->suffix('%')
                            ->numeric(decimalPlaces: 2),
                        Infolists\Components\TextEntry::make('soil_texture')
                            ->label('Soil Texture')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->texture?->name ?? null;
                            })
                            ->badge(),
                        Infolists\Components\TextEntry::make('soil_health_score')
                            ->label('Soil Health Score')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->getSoilHealthScore();
                            })
                            ->suffix('/100')
                            ->badge()
                            ->color(fn (?int $state) => match (true) {
                                $state === null => 'gray',
                                $state >= 80 => 'success',
                                $state >= 60 => 'info',
                                $state >= 40 => 'warning',
                                default => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('soil_test_date')
                            ->label('Last Soil Test')
                            ->getStateUsing(function (CalendarRun $record) {
                                $soil = $record->areaCrop?->area?->soilProfiles()
                                    ->orderByDesc('test_date')->first();

                                return $soil?->test_date;
                            })
                            ->date(),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),

                // Field History Section
                Infolists\Components\Section::make('Field History')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('field_histories')
                            ->label('')
                            ->getStateUsing(function (CalendarRun $record) {
                                return $record->areaCrop?->area?->fieldHistories()
                                    ->with('crop')
                                    ->orderByDesc('season_year')
                                    ->take(5)
                                    ->get() ?? collect();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('season_year')
                                    ->label('Season'),
                                Infolists\Components\TextEntry::make('crop.name')
                                    ->label('Crop')
                                    ->getStateUsing(fn ($record) => $record->crop?->getTranslation('name', app()->getLocale()) ?? 'Fallow'),
                                Infolists\Components\TextEntry::make('actual_yield')
                                    ->label('Yield')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' t/ha'),
                                Infolists\Components\TextEntry::make('planting_date')
                                    ->label('Planted')
                                    ->date(),
                                Infolists\Components\TextEntry::make('harvest_date')
                                    ->label('Harvested')
                                    ->date(),
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // Creation Analysis Section
                Infolists\Components\Section::make('Creation Analysis')
                    ->schema([
                        Infolists\Components\TextEntry::make('metadata.data_quality_score')
                            ->label('Data Quality Score')
                            ->getStateUsing(fn (CalendarRun $record) => $record->metadata['data_quality_score'] ?? $record->metadata['creation']['quality_score'] ?? null)
                            ->suffix('/100')
                            ->badge()
                            ->color(fn (?int $state) => match (true) {
                                $state === null => 'gray',
                                $state >= 80 => 'success',
                                $state >= 60 => 'info',
                                $state >= 40 => 'warning',
                                default => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('metadata.creation.sources')
                            ->label('Data Sources')
                            ->getStateUsing(function (CalendarRun $record) {
                                $sources = $record->metadata['creation']['sources'] ?? $record->metadata['data_sources'] ?? [];

                                return is_array($sources) ? implode(', ', $sources) : $sources;
                            })
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('metadata.global_day_shift')
                            ->label('Day Shift Applied')
                            ->getStateUsing(fn (CalendarRun $record) => $record->metadata['global_day_shift'] ?? $record->metadata['creation']['day_shift'] ?? 0)
                            ->suffix(' days'),
                        Infolists\Components\TextEntry::make('metadata.creation.warnings')
                            ->label('Creation Warnings')
                            ->getStateUsing(function (CalendarRun $record) {
                                $warnings = $record->metadata['creation']['warnings'] ?? $record->metadata['warnings'] ?? [];

                                return is_array($warnings) && count($warnings) > 0 ? implode('; ', $warnings) : 'None';
                            })
                            ->badge()
                            ->color(fn (string $state) => $state === 'None' ? 'success' : 'warning'),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),

            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalendarRuns::route('/'),
            'view' => Pages\ViewCalendarRun::route('/{record}'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return collect(CalendarRunStatus::cases())
            ->mapWithKeys(fn (CalendarRunStatus $status): array => [
                $status->value => str($status->name)->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }
}
