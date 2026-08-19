<?php

declare(strict_types=1);

namespace Modules\General\Services\Content;

use Modules\General\Models\ContentSource;
use Modules\General\Services\Content\Crawlers\TelegramPublicChannelCrawler;
use Modules\General\Services\Content\Crawlers\YtDlpSourceCrawler;

final class ContentSourceCrawler
{
    public function __construct(
        private readonly TelegramPublicChannelCrawler $telegramPublicChannelCrawler,
        private readonly YtDlpSourceCrawler $ytDlpSourceCrawler,
    ) {}

    /**
     * @return array{created: int, skipped: int, downloaded: int}
     */
    public function crawl(ContentSource $source, int $limit, bool $download): array
    {
        if ($source->source_type === 'telegram') {
            return $this->telegramPublicChannelCrawler->crawl($source, $limit, $download);
        }

        if ($this->ytDlpSourceCrawler->supports($source)) {
            return $this->ytDlpSourceCrawler->crawl($source, $limit, $download);
        }

        return [
            'created' => 0,
            'skipped' => 0,
            'downloaded' => 0,
        ];
    }
}
