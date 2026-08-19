<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ParamOption extends Model
{
    use HasTranslations;

    protected $fillable = [
        'param_id',
        'value',
        'label',
        'description',
    ];

    public array $translatable = ['label'];

    protected $casts = [
        'label' => 'array',
    ];

    public function param(): BelongsTo
    {
        return $this->belongsTo(Param::class);
    }
}
