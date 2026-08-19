<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Agronom\Models\ServiceRequest;

class LatestServiceRequestsWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Service Requests';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServiceRequest::query()
                    ->with(['farmer', 'agronom', 'service.serviceType'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('farmer.name')
                    ->label('Farmer')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('service.serviceType.name')
                    ->label('Service')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state['en'] ?? $state['uz'] ?? '—';
                        }

                        return $state ?? '—';
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => fn ($state) => in_array($state, ['cancelled', 'rejected']),
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ])
            ->paginated(false);
    }
}
