<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\AgroCalculator\Filament\Resources\ScoringRunResource\Pages;
use Modules\AgroCalculator\Models\ScoringRun;

class ScoringRunResource extends Resource
{
    protected static ?string $model = ScoringRun::class;

    protected static ?string $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __('agrocalculator::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_run.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_run.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('agrocalculator::filament.resources.scoring_run.plural_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('agrocalculator::filament.resources.scoring_run.table.columns.id'))
                    ->sortable(),
                TextColumn::make('planting.crop.name')
                    ->label(__('agrocalculator::filament.resources.scoring_run.table.columns.crop'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('score')
                    ->label(__('agrocalculator::filament.resources.scoring_run.table.columns.score')),
                TextColumn::make('grade')
                    ->label(__('agrocalculator::filament.resources.scoring_run.table.columns.grade')),
                TextColumn::make('created_at')
                    ->label(__('agrocalculator::filament.resources.scoring_run.table.columns.created_at'))
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScoringRuns::route('/'),
            'view' => Pages\ViewScoringRun::route('/{record}'),
        ];
    }
}
