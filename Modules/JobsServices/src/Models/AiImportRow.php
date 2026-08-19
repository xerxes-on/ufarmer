<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\JobsServices\Enums\AiImportRowStatus;

/**
 * One staged record from an AI import, awaiting review (UFARM-2644).
 *
 * The entity-specific payload lives in `data` rather than in columns, which is
 * what keeps the staging tables reusable across importers. `raw` keeps the
 * model's verbatim output so a mapping bug is diagnosable without re-running —
 * and re-paying for — the extraction.
 *
 * Errors are split into `blocking` (row cannot publish) and `warnings` (row
 * publishes, but an admin should look). Only blocking errors gate publishing;
 * a fallback coordinate or a negotiable price is worth surfacing, not worth
 * stopping an import over.
 *
 * @property-read AiImportRowStatus $status
 */
class AiImportRow extends Model
{
    protected $table = 'ai_import_rows';

    protected $guarded = ['id'];

    protected $casts = [
        'status' => AiImportRowStatus::class,
        'row_index' => 'integer',
        'data' => 'array',
        'raw' => 'array',
        'errors' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AiImportBatch::class, 'ai_import_batch_id');
    }

    /**
     * Whatever the publisher created from this row — a UserProfile for the
     * worker importer. Nullable until the row is published.
     *
     * Named `createdRecord`, not `created`: `Model::created()` is Eloquent's
     * static event registrar, and redeclaring it as an instance method is a
     * fatal error.
     *
     * The morph columns are passed explicitly rather than via `morphTo('created')`,
     * which would name the relation `created` and file eager-loaded results
     * under that key — leaving `with('createdRecord')` silently resolving to
     * null. Columns stay `created_type`/`created_id`; only the accessor differs.
     */
    public function createdRecord(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'created_type', 'created_id');
    }

    /**
     * Read one mapped value out of the entity-specific payload. Supports dot
     * notation so nested keys stay reachable from Filament and Blade.
     */
    public function value(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * @return array<int, string>
     */
    public function blockingErrors(): array
    {
        return array_values((array) ($this->errors['blocking'] ?? []));
    }

    /**
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return array_values((array) ($this->errors['warnings'] ?? []));
    }

    /** No blocking errors — the row is safe to turn into real records. */
    public function isValid(): bool
    {
        return $this->blockingErrors() === [];
    }

    /**
     * Constrain a query to rows with an empty `errors->blocking` array.
     *
     * Written portably rather than with `jsonb_array_length`: production runs
     * Postgres but the test suite runs SQLite, and a Postgres-only fragment
     * here would make this untestable. `whereJsonLength` compiles correctly on
     * both.
     */
    public static function scopeWithoutBlockingErrors(Builder $query): Builder
    {
        return $query->whereJsonLength('errors->blocking', 0);
    }

    public function isPublishable(): bool
    {
        return $this->status === AiImportRowStatus::Draft && $this->isValid();
    }

    /**
     * Translated, human-readable problems for the review table. Error keys are
     * translated through the shared `ai_import.errors` block so the same key
     * reads the same wherever it surfaces.
     *
     * @return array<int, string>
     */
    public function problemLabels(): array
    {
        return array_map(
            static fn (string $key): string => __('admin-panel.resources.ai_import.errors.'.$key),
            [...$this->blockingErrors(), ...$this->warnings()],
        );
    }
}
