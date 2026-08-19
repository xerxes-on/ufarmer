<?php

declare(strict_types=1);

namespace Modules\General\Console\Commands;

use Illuminate\Console\Command;
use Modules\General\Models\ContentSource;
use Modules\General\Services\Content\ContentSourceCrawler;
use Throwable;

class CrawlContentSourcesCommand extends Command
{
    protected $signature = 'content:crawl-sources
        {source_id? : Crawl only one content source ID}
        {--limit=5 : Max latest items per source}
        {--download : Download media to S3 immediately}';

    protected $description = 'Crawl configured content sources and create approval drafts.';

    public function handle(ContentSourceCrawler $crawler): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $sourceId = $this->argument('source_id');

        $sources = ContentSource::query()
            ->where('is_active', true)
            ->when($sourceId !== null, fn ($query) => $query->whereKey($sourceId))
            ->orderBy('id')
            ->get();

        if ($sources->isEmpty()) {
            $this->warn('No active content sources found.');

            return self::SUCCESS;
        }

        foreach ($sources as $source) {
            try {
                $result = $crawler->crawl($source, $limit, (bool) $this->option('download'));

                $this->info(sprintf(
                    'Source #%d %s: created=%d skipped=%d downloaded=%d',
                    $source->id,
                    $source->name,
                    $result['created'],
                    $result['skipped'],
                    $result['downloaded'],
                ));
            } catch (Throwable $exception) {
                $this->error(sprintf(
                    'Source #%d %s failed: %s',
                    $source->id,
                    $source->name,
                    $exception->getMessage(),
                ));
            }
        }

        return self::SUCCESS;
    }
}
