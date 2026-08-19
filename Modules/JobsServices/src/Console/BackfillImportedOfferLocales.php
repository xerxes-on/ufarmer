<?php

declare(strict_types=1);

namespace Modules\JobsServices\Console;

use Illuminate\Console\Command;
use Modules\JobsServices\Services\AiImport\Worker\ImportedOfferLocaleBackfill;

final class BackfillImportedOfferLocales extends Command
{
    protected $signature = 'jobsservices:backfill-offer-locales
        {--apply : Translate and persist candidates; without this flag the command only counts them}
        {--limit= : Maximum number of candidates to process}';

    protected $description = 'Backfill configured locales for spreadsheet-imported service offers';

    public function handle(ImportedOfferLocaleBackfill $backfill): int
    {
        $limit = $this->option('limit');
        $limit = is_numeric($limit) && (int) $limit > 0 ? (int) $limit : null;
        $apply = (bool) $this->option('apply');
        $stats = $backfill->execute($apply, $limit);

        $this->table(['Candidates', 'Translated', 'Title updates', 'Description updates'], [[
            $stats['candidates'],
            $stats['translated'],
            $stats['title_updates'],
            $stats['description_updates'],
        ]]);

        if (! $apply) {
            $this->warn('Dry run only. Use --apply to translate and persist these import-linked candidates.');
        }

        return self::SUCCESS;
    }
}
