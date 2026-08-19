<?php

declare(strict_types=1);

namespace Modules\JobsServices\Enums;

/**
 * What an `ai_import_batches` row imports.
 *
 * The staging tables are entity-agnostic on purpose (UFARM-2644): adding an
 * importer means a new case here plus its mapper/schema pair, not a new pair
 * of `*_import_*` tables.
 */
enum AiImportEntity: string
{
    case WORKER = 'worker';

    public function label(): string
    {
        return match ($this) {
            self::WORKER => __('admin-panel.resources.ai_import.entities.worker'),
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
