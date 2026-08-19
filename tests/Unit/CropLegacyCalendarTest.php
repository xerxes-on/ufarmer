<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Crops\Models\Crop;
use Tests\TestCase;

class CropLegacyCalendarTest extends TestCase
{
    public function test_missing_legacy_calendar_tables_do_not_break_a_crop(): void
    {
        self::assertFalse((new Crop)->in_agrocalendar);
    }
}
