<?php

declare(strict_types=1);

namespace Modules\Agronom\Models;

use App\Support\InteractsWithMediaPostgres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AgronomDetail extends Model implements HasMedia
{
    use InteractsWithMedia;
    use InteractsWithMediaPostgres {
        InteractsWithMediaPostgres::media insteadof InteractsWithMedia;
    }

    public const string MEDIA_COLLECTION_CERTIFICATES = 'certificates';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specializations' => 'array',
            'years_of_experience' => 'integer',
            'price' => 'integer',
            'rating' => 'decimal:2',
            'total_ratings' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_CERTIFICATES)
            ->useDisk(config('media-library.disk_name', 'public'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agronomServices(): HasMany
    {
        return $this->hasMany(Service::class, 'user_id', 'user_id');
    }

    public function agronomRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'agronom_id', 'user_id');
    }

    public function specializationRelations(): BelongsToMany
    {
        return $this->belongsToMany(
            Specialization::class,
            'agronom_specializations',
            'agronom_detail_id',
            'specialization_id'
        )->withTimestamps();
    }
}
