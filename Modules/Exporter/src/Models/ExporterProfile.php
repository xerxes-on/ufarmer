<?php

declare(strict_types=1);

namespace Modules\Exporter\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Exporter\Enums\ExporterAccessRequestStatus;

/**
 * @property int $id
 * @property int $user_id
 * @property int $auth_id
 * @property string|null $company_name
 * @property string|null $full_name
 * @property string|null $position
 * @property string|null $license_number
 * @property string|null $inn
 * @property string|null $bio
 * @property array|null $interested_crops
 * @property array|null $interested_regions
 * @property bool $is_verified
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 * @property int|null $exporter_role_id
 * @property ExporterAccessRequestStatus|null $access_request_status
 * @property Carbon|null $access_requested_at
 * @property string|null $access_request_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read ExporterRole|null $role
 */
class ExporterProfile extends Model
{
    protected $fillable = [
        'user_id',
        'auth_id',
        'company_name',
        'full_name',
        'position',
        'license_number',
        'inn',
        'bio',
        'interested_crops',
        'interested_regions',
        'is_verified',
        'verified_at',
        'verified_by',
        'exporter_role_id',
        'access_request_status',
        'access_requested_at',
        'access_request_reason',
    ];

    protected $casts = [
        'interested_crops' => 'array',
        'interested_regions' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'access_request_status' => ExporterAccessRequestStatus::class,
        'access_requested_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExporterRole, self>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(ExporterRole::class, 'exporter_role_id');
    }

    public function verify(int $verifiedBy): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
        ]);
    }

    /**
     * Mirrors the enforce_all_access_exporter_profile_requirements() Postgres
     * trigger on exporter_profiles — checked up front so an ineligible profile
     * gets a clear Filament notice instead of a raw SQL exception.
     */
    public function isEligibleForUnlimitedRole(): bool
    {
        return trim((string) $this->full_name) !== ''
            && trim((string) $this->company_name) !== ''
            && trim((string) $this->inn) !== ''
            && $this->access_request_status === ExporterAccessRequestStatus::APPROVED
            && $this->access_requested_at !== null
            && $this->is_verified === true
            && $this->verified_at !== null
            && $this->verified_by !== null;
    }
}
