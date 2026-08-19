<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Custom MorphMany relationship that casts model_id to string for PostgreSQL.
 *
 * This handles the case where media.model_id is varchar but parent models use integer IDs.
 *
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 *
 * @extends MorphMany<TRelatedModel, TDeclaringModel>
 */
class PostgresMorphMany extends MorphMany
{
    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array<int, TDeclaringModel>  $models
     */
    public function addEagerConstraints(array $models): void
    {
        // Cast all keys to strings for PostgreSQL varchar comparison
        $keys = $this->getKeys($models, $this->localKey);
        $stringKeys = array_map('strval', $keys);

        $this->query->where($this->morphType, $this->morphClass);

        $this->query->whereIn($this->foreignKey, $stringKeys);
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array<int, TDeclaringModel>  $models
     * @param  Collection<int, TRelatedModel>  $results
     * @param  string  $relation
     * @return array<int, TDeclaringModel>
     */
    public function match(array $models, Collection $results, $relation): array
    {
        return $this->matchMany($models, $results, $relation);
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param  array<int, TDeclaringModel>  $models
     * @param  Collection<int, TRelatedModel>  $results
     * @param  string  $relation
     * @return array<int, TDeclaringModel>
     */
    public function matchMany(array $models, Collection $results, $relation): array
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            // Use string key for matching since model_id is varchar
            $key = (string) $model->getAttribute($this->localKey);

            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $this->related->newCollection($dictionary[$key]));
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  Collection<int, TRelatedModel>  $results
     * @return array<string, array<int, TRelatedModel>>
     */
    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            // model_id is already a string since it's varchar
            $foreignKeyValue = $result->{$this->getForeignKeyName()};
            $dictionary[$foreignKeyValue][] = $result;
        }

        return $dictionary;
    }
}
