<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Agronom\Enums\ServiceRequestStatus;
use Modules\Agronom\Enums\ServiceRequestType;
use Modules\Agronom\Filament\Resources\ServiceRequestResource;
use Modules\Agronom\Models\ServiceRequest;

class ServiceRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceRequests';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.agronom_service_requests');
    }

    protected static ?string $icon = 'heroicon-o-clipboard-document-list';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['farmer', 'agronom', 'service']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_role')
                    ->label('User Role')
                    ->state(fn (ServiceRequest $record): string => $record->farmer_id === $this->getOwnerRecord()->getKey()
                        ? 'Farmer'
                        : 'Agronom')
                    ->badge(),
                Tables\Columns\TextColumn::make('farmer.name')
                    ->label('Farmer')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('agronom.name')
                    ->label('Agronom')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->placeholder('-')
                    ->wrap(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (ServiceRequestType $state): string => $state->label())
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (ServiceRequestStatus $state): string => $state->label())
                    ->badge(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('UZS')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('scheduled_time')
                    ->label('Scheduled')
                    ->dateTime()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ServiceRequest $record): string => ServiceRequestResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
