<?php

declare(strict_types=1);

namespace Modules\JobsServices\Enums;

/**
 * Lifecycle of a single staged `ai_import_rows` record.
 *
 * Only `Draft` rows publish, which is what makes a re-publish safe: rows
 * already turned into real records are skipped rather than duplicated.
 */
enum AiImportRowStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __('admin-panel.resources.ai_import.row_statuses.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'success',
            self::Skipped => 'warning',
        };
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
