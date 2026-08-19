<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Worker profile — the "worker" half of a user account.
 *
 * Mirrors ufarm-api's Modules\Services\Models\UserProfile against the same
 * shared `user_profiles` table. Only columns that actually exist in the
 * shared schema are declared here: ufarm-api's model additionally lists
 * working_hours_from/to, education and price, but those columns are not
 * present in the deployed table (ufarm-api guards every write with
 * Schema::hasColumn for that reason). Declaring them here would let the
 * Filament form build an INSERT that errors at the database.
 */
class UserProfile extends Model
{
    protected $table = 'user_profiles';

    protected $guarded = [];

    protected $casts = [
        'experience_years' => 'float',
        'specializations' => 'array',
        'is_available' => 'boolean',
        'rating' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'rating_total' => 'integer',
        'reviews_count' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Service offers published by this worker.
     *
     * `service_offers` is keyed by `user_id` (the account), not by the
     * profile id, so this hops through the shared user key rather than the
     * usual local primary key.
     */
    public function services(): HasMany
    {
        return $this->hasMany(ServiceOffer::class, 'user_id', 'user_id');
    }
}
