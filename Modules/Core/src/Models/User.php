<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Agronom\Models\AgronomDetail;
use Modules\Agronom\Models\Service;
use Modules\Agronom\Models\ServiceRequest;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\CropPrice;
use Modules\Exporter\Models\ExporterProfile;
use Modules\JobsServices\Models\ServiceOffer;
use Modules\JobsServices\Models\UserProfile;
use Modules\Uzgidromet\Support\HasRoles;
use Musonza\Chat\Traits\Messageable;

class User extends Authenticatable
{
    use HasRoles;
    use Messageable;
    use SoftDeletes;

    protected $guarded = [];

    // Disable remember me tokens since the users table lacks the column.
    protected $rememberTokenName = '';

    public function detail(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    public function agronomDetail(): HasOne
    {
        return $this->hasOne(AgronomDetail::class);
    }

    public function myIdIdentity(): HasOne
    {
        return $this->hasOne(MyIdIdentityVerification::class)
            ->where('provider', 'myid');
    }

    public function exporterProfile(): HasOne
    {
        return $this->hasOne(ExporterProfile::class);
    }

    public function workerProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function agronomServices(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function serviceOffers(): HasMany
    {
        return $this->hasMany(ServiceOffer::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'farmer_id')
            ->orWhere('agronom_id', $this->getKey());
    }

    public function crops(): BelongsToMany
    {
        return $this->belongsToMany(Crop::class, 'user_crops')
            ->withTimestamps();
    }

    public function cropPrices(): HasMany
    {
        return $this->hasMany(CropPrice::class);
    }
}
