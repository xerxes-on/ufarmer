<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Modules\JobsServices\Models\ServiceCategory;
use Tests\TestCase;

final class ServiceCategoryBannerTest extends TestCase
{
    public function test_category_banner_is_single_file_on_the_configured_media_disk(): void
    {
        $category = new ServiceCategory;
        $category->registerMediaCollections();

        $collection = $category->getRegisteredMediaCollections()
            ->firstWhere('name', ServiceCategory::MEDIA_COLLECTION_BANNER);

        $this->assertNotNull($collection);
        $this->assertTrue($collection->singleFile);
        $this->assertSame(config('media-library.disk_name', 'public'), $collection->diskName);
    }
}
