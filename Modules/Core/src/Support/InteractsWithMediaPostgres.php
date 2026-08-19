<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait to handle PostgreSQL type casting for Spatie Media Library.
 *
 * Use this trait AFTER InteractsWithMedia in models with integer primary keys
 * when the media table's model_id column is varchar (for UUID support).
 */
trait InteractsWithMediaPostgres
{
    /**
     * Override the media relationship to cast model_id to string for PostgreSQL compatibility.
     *
     * @return PostgresMorphMany<\Spatie\MediaLibrary\MediaCollections\Models\Media, $this>
     */
    public function media(): PostgresMorphMany
    {
        /** @var Model $this */
        $instance = $this->newRelatedInstance($this->getMediaModel());

        [$type, $id] = $this->getMorphs('model', null, null);

        $table = $instance->getTable();

        $localKey = $this->getKeyName();

        return new PostgresMorphMany(
            $instance->newQuery(),
            $this,
            $table.'.'.$type,
            $table.'.'.$id,
            $localKey
        );
    }
}
