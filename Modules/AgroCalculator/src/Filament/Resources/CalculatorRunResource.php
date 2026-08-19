<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Modules\AgroCalculator\Filament\Resources\CalculatorRunResource\Pages;
use Modules\AgroCalculator\Models\CalculatorRun;

class CalculatorRunResource extends Resource
{
    protected static ?string $model = CalculatorRun::class;

    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('agrocalculator::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('agrocalculator::filament.resources.calculator_run.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('agrocalculator::filament.resources.calculator_run.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('agrocalculator::filament.resources.calculator_run.plural_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('agrocalculator::filament.resources.calculator_run.table.columns.id'))
                    ->sortable(),
                TextColumn::make('planting.crop.name')
                    ->label(__('agrocalculator::filament.resources.calculator_run.table.columns.crop'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('potential_yield_t_ha')
                    ->label(__('agrocalculator::filament.resources.calculator_run.table.columns.yield')),
                TextColumn::make('risk_level')
                    ->label(__('agrocalculator::filament.resources.calculator_run.table.columns.risk_level'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('agrocalculator::filament.resources.calculator_run.table.columns.ran_at'))
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorRuns::route('/'),
            'view' => Pages\ViewCalculatorRun::route('/{record}'),
        ];
    }
}
