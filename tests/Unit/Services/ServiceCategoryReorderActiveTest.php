<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\JobsServices\Models\ServiceCategory;
use Tests\TestCase;

class ServiceCategoryReorderActiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated in-memory sqlite connection (see EmployeeResourceScopingTest)
        // with a partial unique index mirroring the ufarm-api migration for
        // UFARM-2559, so a naive single-statement swap fails here the same
        // way it does against the real Postgres index.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('service_categories', function ($table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        DB::statement(
            'CREATE UNIQUE INDEX service_categories_active_sort_order_unique '.
            'ON service_categories (sort_order) WHERE is_active = 1 AND deleted_at IS NULL'
        );
    }

    public function test_reorder_active_swaps_positions_without_transient_collision(): void
    {
        $ids = [];
        foreach ([1, 2, 3, 4] as $sortOrder) {
            $ids[] = DB::table('service_categories')->insertGetId(['is_active' => true, 'sort_order' => $sortOrder]);
        }

        // Move the last category to first (a swap-heavy reorder), the shape
        // that broke a plain single-statement UPDATE against the partial
        // unique index.
        ServiceCategory::reorderActive([$ids[3], $ids[1], $ids[2], $ids[0]]);

        $ordered = DB::table('service_categories')->orderBy('sort_order')->pluck('id')->all();

        $this->assertSame([$ids[3], $ids[1], $ids[2], $ids[0]], $ordered);
        $this->assertEqualsCanonicalizing([1, 2, 3, 4], DB::table('service_categories')->pluck('sort_order')->all());
    }
}
