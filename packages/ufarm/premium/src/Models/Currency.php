<?php

declare(strict_types=1);

namespace Ufarm\Premium\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $table = 'currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function premiumPlans(): HasMany
    {
        return $this->hasMany(PremiumPlan::class, 'currency_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
