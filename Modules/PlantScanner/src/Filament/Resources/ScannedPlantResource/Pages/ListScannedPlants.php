<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources\ScannedPlantResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\PlantScanner\Enums\ScanStatus;
use Modules\PlantScanner\Filament\Resources\ScannedPlantResource;

class ListScannedPlants extends ListRecords
{
    protected static string $resource = ScannedPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'recognition' => Tab::make('Recognition')
                ->icon('heroicon-o-sparkles')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('scan_mode', 'recognition')),
            'pests' => Tab::make('Pests')
                ->icon('heroicon-o-bug-ant')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('scan_mode', 'pests')),
            'diagnosis' => Tab::make('Diagnosis')
                ->icon('heroicon-o-heart')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('scan_mode', 'diagnosis')),
            'not_identified' => Tab::make('Not Identified')
                ->icon('heroicon-o-question-mark-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('status', ScanStatus::Completed->value)
                    ->where(function (Builder $query): void {
                        $query
                            ->where(function (Builder $query): void {
                                $query
                                    ->where('scan_mode', 'recognition')
                                    ->whereNull('plant_detail_id');
                            })
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->where('scan_mode', 'pests')
                                    ->whereNull('pest_detail_id')
                                    ->where(function (Builder $query): void {
                                        $query
                                            ->whereNull('identified_insect_name')
                                            ->orWhere('identified_insect_name', '');
                                    });
                            })
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->where('scan_mode', 'diagnosis')
                                    ->where(function (Builder $query): void {
                                        $query
                                            ->whereNull('identified_disease_name')
                                            ->orWhere('identified_disease_name', '');
                                    })
                                    ->where(function (Builder $query): void {
                                        $query
                                            ->whereNull('ai_enriched_data->disease_name->en')
                                            ->orWhere('ai_enriched_data->disease_name->en', '');
                                    });
                            });
                    })),
        ];
    }
}
