<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

/**
 * One AI call's decoded payload plus its billing footprint (UFARM-2644).
 *
 * A single import can span several calls — one per chunk, plus the taxonomy
 * resolution pass — so `merge()` folds them into one set of totals that lands
 * on the batch. Without that, the recorded cost of a chunked import would be
 * whichever call happened to finish last.
 */
final readonly class ExtractionResult
{
    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    public function __construct(
        public array $records = [],
        public ?string $model = null,
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
        public ?float $cost = null,
    ) {}

    /**
     * Fold another call's result into this one.
     *
     * Token counts and cost add up; the model name keeps the first non-null,
     * since a fallback route means later chunks may report a different model
     * and the first is the one actually requested.
     */
    public function merge(self $other): self
    {
        return new self(
            records: [...$this->records, ...$other->records],
            model: $this->model ?? $other->model,
            promptTokens: self::add($this->promptTokens, $other->promptTokens),
            completionTokens: self::add($this->completionTokens, $other->completionTokens),
            cost: self::addFloat($this->cost, $other->cost),
        );
    }

    public function count(): int
    {
        return count($this->records);
    }

    /**
     * Null + null stays null — "the provider reported no usage" and "usage was
     * zero" are different facts, and only the latter should display as 0.
     */
    private static function add(?int $a, ?int $b): ?int
    {
        if ($a === null && $b === null) {
            return null;
        }

        return (int) $a + (int) $b;
    }

    private static function addFloat(?float $a, ?float $b): ?float
    {
        if ($a === null && $b === null) {
            return null;
        }

        return (float) $a + (float) $b;
    }
}
