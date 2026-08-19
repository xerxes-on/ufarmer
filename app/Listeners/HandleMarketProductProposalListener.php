<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MarketProductProposalReceivedEvent;
use App\Models\MarketplaceProposal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

final class HandleMarketProductProposalListener implements ShouldQueue
{
    public string $queue = 'admin-proposals';

    public function handle(MarketProductProposalReceivedEvent $event): void
    {
        $data = $event->data;

        Log::info('[AdminApi] Market product proposal received', [
            'proposal_id' => $data['proposalId'] ?? null,
            'seller_id' => $data['sellerId'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        $proposalId = $data['proposalId'] ?? null;

        if (empty($proposalId)) {
            Log::warning('[AdminApi] Proposal received without proposalId', $data);

            return;
        }

        MarketplaceProposal::updateOrCreate(
            ['proposal_id' => $proposalId],
            [
                'seller_id' => $data['sellerId'] ?? 0,
                'seller_name' => $data['sellerName'] ?? 'Unknown',
                'product_data' => $data['productData'] ?? [],
                'treatment_data' => $data['treatmentData'] ?? [],
                'seller_comment' => $data['sellerComment'] ?? null,
                'status' => $data['status'] ?? 'pending',
            ]
        );

        Log::info('[AdminApi] Marketplace proposal replicated successfully', [
            'proposal_id' => $proposalId,
        ]);
    }

    public function failed(MarketProductProposalReceivedEvent $event, Throwable $exception): void
    {
        Log::error('[AdminApi] Market product proposal processing failed', [
            'data' => $event->data,
            'error' => $exception->getMessage(),
        ]);
    }
}
