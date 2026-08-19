<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProposal extends Model
{
    protected $guarded = [];

    protected $casts = [
        'proposal_id' => 'integer',
        'seller_id' => 'integer',
        'product_data' => 'array',
        'treatment_data' => 'array',
    ];
}
