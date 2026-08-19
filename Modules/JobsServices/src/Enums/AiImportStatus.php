<?php

declare(strict_types=1);

namespace Modules\JobsServices\Enums;

/**
 * Lifecycle of an `ai_import_batches` row.
 *
 * `extracted` is the only state a batch can be published from, and `pending`
 * or `failed` the only states it can be (re)parsed from — that pair of rules
 * is what stops a queue retry from wiping rows an admin already corrected.
 */
enum AiImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Extracted = 'extracted';
    case Failed = 'failed';
    case Published = 'published';

    public function label(): string
    {
        return __('admin-panel.resources.ai_import.statuses.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'warning',
            self::Extracted => 'info',
            self::Failed => 'danger',
            self::Published => 'success',
        };
    }

    /** Work is still in flight, so the review screen should keep polling. */
    public function isInProgress(): bool
    {
        return $this === self::Pending || $this === self::Processing;
    }

    /** Rows may be (re)extracted — never over rows an admin has reviewed. */
    public function isParsable(): bool
    {
        return $this === self::Pending || $this === self::Failed;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $case): array => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
