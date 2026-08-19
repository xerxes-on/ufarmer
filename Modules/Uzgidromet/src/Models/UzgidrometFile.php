<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

class UzgidrometFile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'file_size_bytes' => 'integer',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    protected function fileSizeHuman(): Attribute
    {
        return Attribute::get(function (): string {
            $bytes = (int) $this->file_size_bytes;
            $units = ['B', 'KB', 'MB', 'GB'];
            $i = 0;
            $value = (float) $bytes;
            while ($value >= 1024 && $i < count($units) - 1) {
                $value /= 1024;
                $i++;
            }

            return sprintf('%.2f %s', $value, $units[$i]);
        });
    }
}
