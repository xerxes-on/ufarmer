<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\RegionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Core\Models\Param;
use Modules\Crops\Models\Unit;

class RegionParamsRelationManager extends RelationManager
{
    protected static string $relationship = 'paramValues';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.region_parameters');
    }

    protected static ?string $recordTitleAttribute = 'param.name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Parameter Assignment')
                ->schema([
                    Forms\Components\Select::make('param_id')
                        ->label('Parameter')
                        ->relationship('param', 'id')
                        ->getOptionLabelFromRecordUsing(fn (Param $record): string => sprintf(
                            '%s (%s)',
                            $record->getTranslation('name', 'uz'),
                            $record->unit_id ? Unit::find($record->unit_id)?->getTranslation('name', 'uz') ?? '' : ''
                        ))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Forms\Components\Section::make('Value')
                ->schema([
                    Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->numeric()
                        ->step(0.0001)
                        ->required()
                        ->helperText('Parameter value for this region'),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('Optional notes or context'),
                ])
                ->columns(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('param.name')
                    ->label('Parameter')
                    ->getStateUsing(fn ($record) => $record->param?->getTranslation('name', 'uz') ?? '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('param.unit')
                    ->label('Unit')
                    ->getStateUsing(function ($record): string {
                        if (! $record->param?->unit_id) {
                            return '-';
                        }
                        $unit = Unit::find($record->param->unit_id);

                        return $unit ? $unit->getTranslation('name', 'uz') : '-';
                    })
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
