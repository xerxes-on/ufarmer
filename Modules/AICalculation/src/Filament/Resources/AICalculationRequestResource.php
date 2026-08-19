<?php

declare(strict_types=1);

namespace Modules\AICalculation\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Modules\AICalculation\Filament\Resources\AICalculationRequestResource\Pages;
use Modules\AICalculation\Models\AICalculationRequest;

class AICalculationRequestResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AICalculationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = NavigationGroup::Diagnostics->value;

    protected static ?int $navigationSort = 50;

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.id'))
                    ->sortable(),
                TextColumn::make('job_uuid')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.job_uuid'))
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                TextColumn::make('user.auth_id')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.user'))
                    ->searchable(),
                TextColumn::make('area.name')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.area'))
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.status'))
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'cancelled',
                    ])
                    ->sortable(),
                TextColumn::make('requestCrops_count')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.crops_count'))
                    ->counts('requestCrops'),
                TextColumn::make('soilDocuments_count')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.soil_docs_count'))
                    ->counts('soilDocuments'),
                TextColumn::make('waterDocuments_count')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.water_docs_count'))
                    ->counts('waterDocuments'),
                TextColumn::make('farming_start_date')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.farming_start_date'))
                    ->date(),
                TextColumn::make('submitted_at')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.submitted_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.completed_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAICalculationRequests::route('/'),
            'view' => Pages\ViewAICalculationRequest::route('/{record}'),
        ];
    }

    protected static function translationKey(): string
    {
        return 'ai_calculation_request';
    }
}
