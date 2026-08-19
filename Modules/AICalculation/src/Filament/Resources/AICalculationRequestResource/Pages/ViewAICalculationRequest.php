<?php

declare(strict_types=1);

namespace Modules\AICalculation\Filament\Resources\AICalculationRequestResource\Pages;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Modules\AICalculation\Filament\Resources\AICalculationRequestResource;

class ViewAICalculationRequest extends ViewRecord
{
    protected static string $resource = AICalculationRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('ai-calculation::filament.resources.ai_calculation_request.view.request_info'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.id')),
                                TextEntry::make('job_uuid')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.job_uuid'))
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'processing' => 'primary',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'secondary',
                                    }),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('user.auth_id')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.user')),
                                TextEntry::make('area.name')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.area')),
                                TextEntry::make('farming_start_date')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.farming_start_date'))
                                    ->date(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('submitted_at')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.submitted_at'))
                                    ->dateTime(),
                                TextEntry::make('sent_to_n8n_at')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.sent_to_n8n_at'))
                                    ->dateTime(),
                                TextEntry::make('completed_at')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.table.columns.completed_at'))
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make(__('ai-calculation::filament.resources.ai_calculation_request.view.crops'))
                    ->schema([
                        RepeatableEntry::make('requestCrops')
                            ->schema([
                                TextEntry::make('crop.localized_name')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.crop_name')),
                                TextEntry::make('cropVariety.localized_name')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.variety_name')),
                                TextEntry::make('planting_date')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.planting_date'))
                                    ->date(),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(),

                Section::make(__('ai-calculation::filament.resources.ai_calculation_request.view.documents'))
                    ->schema([
                        RepeatableEntry::make('soilDocuments')
                            ->schema([
                                TextEntry::make('original_filename')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.filename')),
                                TextEntry::make('file_size_formatted')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.file_size')),
                                TextEntry::make('url')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.url'))
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab(),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(),

                Section::make(__('ai-calculation::filament.resources.ai_calculation_request.view.result'))
                    ->schema([
                        TextEntry::make('result.overall_rating')
                            ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.overall_rating')),
                        TextEntry::make('result.confidence_score')
                            ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.confidence_score')),
                        TextEntry::make('result.summary')
                            ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.summary'))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->result !== null)
                    ->collapsible(),

                Section::make(__('ai-calculation::filament.resources.ai_calculation_request.view.alternative_crops'))
                    ->schema([
                        RepeatableEntry::make('result.alternativeCrops')
                            ->schema([
                                TextEntry::make('rank')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.rank')),
                                TextEntry::make('localized_name')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.crop_name')),
                                TextEntry::make('suitability_score')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.suitability_score')),
                                TextEntry::make('localized_reasoning')
                                    ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.reasoning'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn ($record) => $record->result?->alternativeCrops->isNotEmpty())
                    ->collapsible(),

                Section::make(__('ai-calculation::filament.resources.ai_calculation_request.view.error'))
                    ->schema([
                        TextEntry::make('error_payload')
                            ->label(__('ai-calculation::filament.resources.ai_calculation_request.view.error_details'))
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->error_payload !== null)
                    ->collapsed(),
            ]);
    }
}
