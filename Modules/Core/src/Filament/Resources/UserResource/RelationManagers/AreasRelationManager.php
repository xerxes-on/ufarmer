<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Core\Filament\Resources\AreaResource;
use Modules\Core\Models\Area;

class AreasRelationManager extends RelationManager
{
    protected static string $relationship = 'areas';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.areas');
    }

    protected static ?string $icon = 'heroicon-o-map-pin';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('crops'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area')
                    ->label('Polygon Area')
                    ->suffix(' m²'),
                Tables\Columns\TextColumn::make('calculated_area')
                    ->label('Calculated Area')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' ha'),
                Tables\Columns\TextColumn::make('ownership_type')
                    ->label('Ownership')
                    ->badge(),
                Tables\Columns\IconColumn::make('irrigated')
                    ->label('Irrigated')
                    ->boolean(),
                Tables\Columns\TextColumn::make('crops_count')
                    ->label('Crops')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Area $record): string => AreaResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
