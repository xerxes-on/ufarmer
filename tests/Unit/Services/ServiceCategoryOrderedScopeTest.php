<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\JobsServices\Models\ServiceCategory;
use Tests\TestCase;

class ServiceCategoryOrderedScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('service_categories', function ($table) {
            $table->id();
            $table->json('name');
            $table->string('applies_to', 20)->default('both');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_ordered_sorts_by_extracted_translation_not_the_json_column(): void
    {
        // Same sort_order for all three, so the tiebreak is what is asserted.
        // Ordering on the raw json column is an outright SQL error on Postgres
        // ("could not identify an ordering operator for type json"), which is
        // what broke the AI import row editor (UFARM-2669).
        $ids = [];
        foreach (['Yakuniy', 'Boshlangich', 'Ortacha'] as $uz) {
            $ids[$uz] = DB::table('service_categories')->insertGetId([
                'name' => json_encode(['uz' => $uz, 'ru' => $uz, 'en' => $uz]),
                'sort_order' => 1,
            ]);
        }

        $ordered = ServiceCategory::query()->active()->ordered()->pluck('id')->all();

        $this->assertSame([$ids['Boshlangich'], $ids['Ortacha'], $ids['Yakuniy']], $ordered);
    }

    public function test_ordered_falls_back_across_locales_when_uz_is_missing(): void
    {
        $withUz = DB::table('service_categories')->insertGetId([
            'name' => json_encode(['uz' => 'Zebra', 'ru' => 'Zebra', 'en' => 'Zebra']),
            'sort_order' => 1,
        ]);
        $ruOnly = DB::table('service_categories')->insertGetId([
            'name' => json_encode(['ru' => 'Alpha', 'en' => 'Alpha']),
            'sort_order' => 1,
        ]);

        $this->assertSame(
            [$ruOnly, $withUz],
            ServiceCategory::query()->active()->ordered()->pluck('id')->all()
        );
    }

    public function test_ordered_prefers_sort_order_over_name(): void
    {
        $second = DB::table('service_categories')->insertGetId([
            'name' => json_encode(['uz' => 'Alpha']),
            'sort_order' => 5,
        ]);
        $first = DB::table('service_categories')->insertGetId([
            'name' => json_encode(['uz' => 'Zebra']),
            'sort_order' => 1,
        ]);

        $this->assertSame(
            [$first, $second],
            ServiceCategory::query()->active()->ordered()->pluck('id')->all()
        );
    }
}
