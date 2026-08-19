<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MyIdIdentityVerification extends Model
{
    protected $table = 'myid_identity_verifications';

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'issued_at' => 'date',
        'expires_at' => 'date',
        'verified_at' => 'datetime',
        'payload' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
