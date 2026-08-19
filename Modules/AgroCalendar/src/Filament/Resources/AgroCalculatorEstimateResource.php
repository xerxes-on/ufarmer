<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources;

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
use Modules\AgroCalendar\Filament\Resources\AgroCalculatorEstimateResource\Pages;
use Modules\AgroCalendar\Models\AgroCalculatorEstimate;

class AgroCalculatorEstimateResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AgroCalculatorEstimate::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Calculator Estimates';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')->disabled(),
                Forms\Components\TextInput::make('crop_id')->disabled(),
                Forms\Components\TextInput::make('area_id')->disabled(),
                Forms\Components\TextInput::make('total_cost')->disabled(),
                Forms\Components\TextInput::make('cost_per_ha')->disabled(),
                Forms\Components\TextInput::make('est_profit')->disabled(),
                Forms\Components\TextInput::make('roi_percent')->disabled(),
                Forms\Components\KeyValue::make('input_data')->disabled()->columnSpanFull(),
                Forms\Components\KeyValue::make('result_data')->disabled()->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Estimate ID')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('user_id')
                            ->label('User ID')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('crop.name')
                            ->label('Crop')
                            ->getStateUsing(fn (AgroCalculatorEstimate $record) => $record->crop ? self::localized($record->crop->getTranslations('name')) : '-')
                            ->icon('heroicon-o-leaf'),
                        Infolists\Components\TextEntry::make('area.name')
                            ->label('Area')
                            ->icon('heroicon-o-map'),
                        Infolists\Components\TextEntry::make('effective_ha')
                            ->label('Area Size')
                            ->suffix(' ha'),
                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Start Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('farming_goal')
                            ->label('Farming Goal')
                            ->badge()
                            ->color('primary'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Financial Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_cost')
                            ->label('Total Cost')
                            ->numeric(decimalPlaces: 0)
                            ->suffix(' UZS')
                            ->badge()
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('cost_per_ha')
                            ->label('Cost per Ha')
                            ->numeric(decimalPlaces: 0)
                            ->suffix(' UZS'),
                        Infolists\Components\TextEntry::make('est_profit')
                            ->label('Est. Profit')
                            ->numeric(decimalPlaces: 0)
                            ->suffix(' UZS')
                            ->badge()
                            ->color(fn (AgroCalculatorEstimate $record) => ($record->est_profit ?? 0) >= 0 ? 'success' : 'danger'),
                        Infolists\Components\TextEntry::make('roi_percent')
                            ->label('ROI')
                            ->suffix('%')
                            ->badge()
                            ->color(fn (AgroCalculatorEstimate $record) => match (true) {
                                ($record->roi_percent ?? 0) >= 50 => 'success',
                                ($record->roi_percent ?? 0) >= 20 => 'info',
                                ($record->roi_percent ?? 0) >= 0 => 'warning',
                                default => 'danger',
                            }),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Input Data')
                    ->schema([
                        Infolists\Components\TextEntry::make('input_data_json')
                            ->label('')
                            ->getStateUsing(fn (AgroCalculatorEstimate $record) => json_encode($record->input_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Infolists\Components\Section::make('Result Data')
                    ->schema([
                        Infolists\Components\TextEntry::make('result_data_json')
                            ->label('')
                            ->getStateUsing(fn (AgroCalculatorEstimate $record) => json_encode($record->result_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('crop.name')
                    ->label('Crop')
                    ->getStateUsing(fn (AgroCalculatorEstimate $record): string => $record->crop ? self::localized($record->crop->getTranslations('name')) : '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('crop', function (Builder $q) use ($search) {
                            $q->where('name->uz', 'like', "%{$search}%")
                                ->orWhere('name->en', 'like', "%{$search}%")
                                ->orWhere('name->ru', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('effective_ha')
                    ->label('Ha')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('farming_goal')
                    ->label('Goal')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_per_ha')
                    ->label('Cost/Ha')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('est_profit')
                    ->label('Profit')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->color(fn (AgroCalculatorEstimate $record): string => ($record->est_profit ?? 0) >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('roi_percent')
                    ->label('ROI%')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn (AgroCalculatorEstimate $record): string => match (true) {
                        ($record->roi_percent ?? 0) >= 50 => 'success',
                        ($record->roi_percent ?? 0) >= 20 => 'info',
                        ($record->roi_percent ?? 0) >= 0 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('crop_id')
                    ->label('Crop')
                    ->relationship('crop', 'name->en')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('farming_goal')
                    ->label('Goal')
                    ->options(fn (): array => AgroCalculatorEstimate::query()
                        ->whereNotNull('farming_goal')
                        ->distinct()
                        ->pluck('farming_goal', 'farming_goal')
                        ->all()),
                Tables\Filters\Filter::make('created_at')
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
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to_date'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('viewData')
                    ->icon('heroicon-o-code-bracket')
                    ->color('gray')
                    ->fillForm(fn (AgroCalculatorEstimate $record): array => [
                        'input_data' => json_encode($record->input_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                        'result_data' => json_encode($record->result_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    ])
                    ->form([
                        Forms\Components\Textarea::make('input_data')
                            ->label('Input Data')
                            ->rows(10)
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('result_data')
                            ->label('Result Data')
                            ->rows(10)
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgroCalculatorEstimates::route('/'),
            'view' => Pages\ViewAgroCalculatorEstimate::route('/{record}'),
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
