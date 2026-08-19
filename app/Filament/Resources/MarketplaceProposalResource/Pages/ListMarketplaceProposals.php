<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketplaceProposalResource\Pages;

use App\Filament\Resources\MarketplaceProposalResource;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceProposals extends ListRecords
{
    protected static string $resource = MarketplaceProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
