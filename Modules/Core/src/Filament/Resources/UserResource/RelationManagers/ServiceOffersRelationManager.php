<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceOffersRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceOffers';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.worker_service_offers');
    }

    protected static ?string $icon = 'heroicon-o-briefcase';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money(fn ($record): string => $record->currency ?? 'UZS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_unit')
                    ->label('Unit')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('requests_count')
                    ->label('Requests')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Reviews')
                    ->numeric(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
