<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AgronomServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'agronomServices';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.agronom_services');
    }

    protected static ?string $icon = 'heroicon-o-academic-cap';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['serviceType', 'currency', 'region', 'city'])
                ->withCount('serviceRequests'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Type')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('price_chat')
                    ->label('Chat Price')
                    ->money('UZS')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('price_in_person')
                    ->label('In-person Price')
                    ->money('UZS')
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_chat_available')
                    ->label('Chat')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_in_person_available')
                    ->label('In Person')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('service_requests_count')
                    ->label('Requests')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
