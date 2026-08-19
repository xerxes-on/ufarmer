<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketplaceProposalResource\Pages;

use App\Filament\Resources\MarketplaceProposalResource;
use App\Services\ProposalApprovalService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewMarketplaceProposal extends ViewRecord
{
    protected static string $resource = MarketplaceProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Product Proposal')
                ->modalDescription('This will create a new market product and treatments in the system.')
                ->form([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Admin Notes (Optional)')
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    app(ProposalApprovalService::class)->approve($this->getRecord(), $data['admin_notes'] ?? null);
                })
                ->visible(fn (): bool => $this->getRecord()->status === 'pending'),
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reject Product Proposal')
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    app(ProposalApprovalService::class)->reject($this->getRecord(), $data['rejection_reason']);
                })
                ->visible(fn (): bool => $this->getRecord()->status === 'pending'),
        ];
    }
}
