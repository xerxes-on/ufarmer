<?php

declare(strict_types=1);

namespace Modules\JobsServices\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\JobsServices\Enums\AiImportEntity;
use Modules\JobsServices\Enums\AiImportRowStatus;
use Modules\JobsServices\Enums\AiImportStatus;

/**
 * One AI-assisted spreadsheet import (UFARM-2644).
 *
 * Entity-agnostic by design: `entity_type` says what is being imported, and
 * everything specific to that entity lives in the mapper, the JSON schema and
 * each row's `data` — not in this table. `stats` holds per-entity publish
 * tallies for the same reason.
 *
 * @property-read AiImportEntity $entity_type
 * @property-read AiImportStatus $status
 */
class AiImportBatch extends Model
{
    /**
     * How long a batch may sit queued or processing before it is treated as
     * abandoned. Comfortably past the job's own 900s timeout plus its retry
     * backoff, so a slow-but-alive extraction — several sequential AI calls on
     * a chunked sheet — is never declared dead while it is still working.
     */
    public const STALE_AFTER_MINUTES = 20;

    protected $table = 'ai_import_batches';

    protected $guarded = ['id'];

    protected $casts = [
        'entity_type' => AiImportEntity::class,
        'status' => AiImportStatus::class,
        'stats' => 'array',
        'size' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'cost' => 'decimal:6',
        'rows_total' => 'integer',
        'rows_valid' => 'integer',
        'rows_published' => 'integer',
        'parsed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(AiImportRow::class, 'ai_import_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Rows a publish run would actually act on: still drafts, and free of
     * blocking errors. Drives both the confirm-modal count and the publish
     * button's disabled state.
     */
    public function publishableRows(): HasMany
    {
        return $this->rows()
            ->where('status', AiImportRowStatus::Draft)
            ->where(fn (Builder $query) => $query
                ->whereNull('errors')
                ->orWhere(fn (Builder $inner) => AiImportRow::scopeWithoutBlockingErrors($inner)));
    }

    /**
     * A queued batch that no worker ever picked up.
     *
     * Without this a batch whose job landed on an unwatched queue — or whose
     * worker died mid-run — is stranded: not extracted so it cannot publish,
     * still "in progress" so it cannot re-parse. An admin would have to ask an
     * engineer to unstick it. After this long, offer the retry instead.
     *
     * Reads the status enum directly rather than isInProgress(), which
     * consults this method — going through it would recurse forever.
     */
    public function isStale(): bool
    {
        if (! $this->status->isInProgress()) {
            return false;
        }

        $since = $this->status === AiImportStatus::Processing
            ? $this->updated_at
            : $this->created_at;

        return $since !== null && $since->lt(now()->subMinutes(self::STALE_AFTER_MINUTES));
    }

    /**
     * Work is genuinely in flight — queued or running, and not yet abandoned.
     */
    public function isInProgress(): bool
    {
        return $this->status->isInProgress() && ! $this->isStale();
    }

    /**
     * A batch may be re-parsed once it has settled, or once it has clearly
     * been abandoned. Re-parsing discards staged rows, so it deliberately
     * stays an explicit, confirmed admin action.
     *
     * The `rows_published` clause is load-bearing, not defensive. A partly
     * published batch now stays Extracted so the rest can still be published,
     * and re-parsing deletes every staged row — including the published ones,
     * whose real users and offers exist and would be left orphaned with no way
     * back to the import that made them.
     */
    public function isReparsable(): bool
    {
        return ! $this->isInProgress()
            && $this->status !== AiImportStatus::Published
            && (int) $this->rows_published === 0;
    }

    public function isPublishable(): bool
    {
        return $this->status === AiImportStatus::Extracted;
    }

    /**
     * Read one per-entity tally (workers_created, offers_created, …).
     */
    public function stat(string $key): int
    {
        return (int) ($this->stats[$key] ?? 0);
    }
}
