<?php

declare(strict_types=1);

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;

class LegalConfig extends Model
{
    public const TYPE_STRING = 'string';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_FLOAT = 'float';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_JSON = 'json';

    public const TYPE_ENUM = 'enum';

    protected $table = 'legal_configs';

    protected $fillable = [
        'key',
        'value_type',
        'value',
        'description',
        'is_public',
    ];

    protected $casts = [
        'description' => 'array',
        'is_public' => 'boolean',
    ];

    public function getTypedValue(): mixed
    {
        return match ($this->value_type) {
            self::TYPE_INTEGER => (int) $this->value,
            self::TYPE_FLOAT => (float) $this->value,
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => json_decode((string) $this->value, true),
            self::TYPE_ENUM, self::TYPE_STRING => $this->value,
            default => $this->value,
        };
    }
}
