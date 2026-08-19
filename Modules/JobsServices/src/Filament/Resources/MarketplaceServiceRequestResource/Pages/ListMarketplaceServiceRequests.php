<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\MarketplaceServiceRequestResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\JobsServices\Filament\Resources\MarketplaceServiceRequestResource;
use Modules\JobsServices\Models\MarketplaceServiceRequest;

class ListMarketplaceServiceRequests extends ListRecords
{
    protected static string $resource = MarketplaceServiceRequestResource::class;

    private ?array $tabCounts = null;

    public function getTabs(): array
    {
        if (! MarketplaceServiceRequestResource::workerMetadataIsAvailable()) {
            return [];
        }

        $counts = $this->tabCounts();

        return [
            'needs_inspection' => Tab::make(__('admin-panel.resources.marketplace_service_request.tabs.needs_inspection'))
                ->badge($counts['needs_inspection'])
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->needsManualInspection()),
            'pending' => Tab::make(__('admin-panel.resources.marketplace_service_request.tabs.pending'))
                ->badge($counts['pending'])
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending')),
            'all' => Tab::make(__('admin-panel.resources.marketplace_service_request.tabs.all'))
                ->badge($counts['all']),
        ];
    }

    private function tabCounts(): array
    {
        if ($this->tabCounts !== null) {
            return $this->tabCounts;
        }

        $aggregate = MarketplaceServiceRequest::query()
            ->selectRaw(
                'COUNT(*) as total_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count',
                ['pending'],
            )
            ->toBase()
            ->first();

        return $this->tabCounts = [
            'needs_inspection' => MarketplaceServiceRequest::query()->needsManualInspection()->count(),
            'pending' => (int) ($aggregate->pending_count ?? 0),
            'all' => (int) ($aggregate->total_count ?? 0),
        ];
    }
}
