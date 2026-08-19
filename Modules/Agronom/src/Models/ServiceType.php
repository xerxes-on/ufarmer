<?php

declare(strict_types=1);

namespace Modules\Agronom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ServiceType extends Model
{
    use HasTranslations;

    protected $table = 'service_types';

    protected $guarded = [];

    public array $translatable = ['name'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
