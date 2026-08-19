<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\CreateMarketProductFromProposalEvent;
use App\Events\ProposalApprovedEvent;
use App\Events\ProposalRejectedEvent;
use App\Models\MarketplaceProposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProposalApprovalService
{
    public function approve(MarketplaceProposal $proposal, ?string $adminNotes): void
    {
        DB::beginTransaction();

        try {
            $proposal->update([
                'status' => 'approved',
                'admin_notes' => $adminNotes,
            ]);

            event(new ProposalApprovedEvent(
                proposalId: $proposal->proposal_id,
                adminNotes: $adminNotes,
            ));

            event(new CreateMarketProductFromProposalEvent(
                proposalId: $proposal->proposal_id,
                productData: $proposal->product_data,
                treatmentData: $proposal->treatment_data,
            ));

            DB::commit();

            Log::info('[AdminApi] Proposal approved', [
                'proposal_id' => $proposal->proposal_id,
                'admin_notes' => $adminNotes,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[AdminApi] Failed to approve proposal', [
                'proposal_id' => $proposal->proposal_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function reject(MarketplaceProposal $proposal, string $reason): void
    {
        $proposal->update([
            'status' => 'rejected',
            'admin_notes' => $reason,
        ]);

        event(new ProposalRejectedEvent(
            proposalId: $proposal->proposal_id,
            rejectionReason: $reason,
        ));

        Log::info('[AdminApi] Proposal rejected', [
            'proposal_id' => $proposal->proposal_id,
            'reason' => $reason,
        ]);
    }
}
