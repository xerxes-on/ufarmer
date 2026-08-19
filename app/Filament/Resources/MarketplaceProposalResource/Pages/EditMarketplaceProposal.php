<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketplaceProposalResource\Pages;

use App\Filament\Resources\MarketplaceProposalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceProposal extends EditRecord
{
    protected static string $resource = MarketplaceProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
