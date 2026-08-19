<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AgroCalendar\Services\Analysis\CalendarSoilAnalysisResolver;
use Modules\Core\Models\Area;
use PDO;
use Tests\TestCase;

class CalendarSoilAnalysisResolverTest extends TestCase
{
    private string $nearbyDatabase;

    private string $soilgridsDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id')->nullable();
            $table->json('coordinates')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agro_area_analyses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('area_id');
            $table->string('type');
            $table->date('analysis_date');
            $table->string('lab_name')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('details');
            $table->timestamps();
        });

        Schema::create('region_analysis_defaults', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('region_id');
            $table->string('type');
            $table->json('details');
            $table->timestamps();
        });

        $this->nearbyDatabase = tempnam(sys_get_temp_dir(), 'nearby-soil-');
        $this->soilgridsDatabase = tempnam(sys_get_temp_dir(), 'soilgrids-');
        $this->seedNearbyDatabase();
        $this->seedSoilgridsDatabase();

        config()->set('agrocalendar.nearby_analysis_defaults', [
            'enabled' => true,
            'database_path' => $this->nearbyDatabase,
            'from_year' => 2022,
            'to_year' => 2026,
            'radius_km' => 10,
            'nearest_parcels' => 50,
            'minimum_latest_year_rows' => 3,
        ]);
        config()->set('agrocalendar.soilgrids_defaults', [
            'enabled' => true,
            'database_path' => $this->soilgridsDatabase,
            'radius_km' => 15,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('region_analysis_defaults');
        Schema::dropIfExists('agro_area_analyses');
        Schema::dropIfExists('areas');
        @unlink($this->nearbyDatabase);
        @unlink($this->soilgridsDatabase);

        parent::tearDown();
    }

    public function test_it_matches_calendar_fallbacks_and_prefers_a_confirmed_analysis(): void
    {
        $area = Area::query()->create([
            'region_id' => 3,
            'coordinates' => [[40.645778, 72.735898]],
        ]);

        DB::table('region_analysis_defaults')->insert([
            'region_id' => 3,
            'type' => 'soil',
            'details' => json_encode([
                'pH' => 7.17,
                'organic_matter_percent' => 0.8,
                'P_mg_kg' => 19.1,
                'K_mg_kg' => 160,
                'N_mg_kg' => 8.5,
                'texture' => 'silt',
                'FC' => 0.253,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resolver = app(CalendarSoilAnalysisResolver::class);
        $resolved = $resolver->resolve($area);

        $this->assertSame('nearby_cadastre_default+soilgrids', $resolved['source']);
        $this->assertSame(7.17, $resolved['details']['soil_properties']['pH']);
        $this->assertSame(1.1, $resolved['details']['soil_properties']['organic_matter_percent']);
        $this->assertSame('loam', $resolved['details']['soil_properties']['texture']);
        $this->assertSame(27.0, $resolved['details']['macronutrients']['P_mg_kg']);
        $this->assertSame(144.0, $resolved['details']['macronutrients']['K_mg_kg']);
        $this->assertSame(8.5, $resolved['details']['macronutrients']['N_mg_kg']);
        $this->assertSame(0.294, $resolved['details']['hydraulic_properties']['FC']);
        $this->assertSame(1.44, $resolved['details']['hydraulic_properties']['bulk_density']);

        DB::table('agro_area_analyses')->insert([
            'area_id' => $area->id,
            'type' => 'soil',
            'analysis_date' => '2026-07-20',
            'lab_name' => 'Agrolab',
            'confirmed_at' => now(),
            'details' => json_encode([
                'pH' => 6.5,
                'texture' => 'clay',
                'organic_matter_percent' => 2.0,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $confirmed = $resolver->resolve($area);

        $this->assertSame('user_confirmed', $confirmed['source']);
        $this->assertSame('2026-07-20', $confirmed['analysis_date']);
        $this->assertSame('Agrolab', $confirmed['lab_name']);
        $this->assertSame([
            'pH' => 6.5,
            'texture' => 'clay',
            'organic_matter_percent' => 2,
        ], $confirmed['details']['soil_properties']);
        $this->assertArrayNotHasKey('hydraulic_properties', $confirmed['details']);
    }

    private function seedNearbyDatabase(): void
    {
        $pdo = new PDO('sqlite:'.$this->nearbyDatabase);
        $pdo->exec('CREATE TABLE parcel_geometries (
            cadastre_hash TEXT,
            centroid_lng REAL,
            centroid_lat REAL
        )');
        $pdo->exec('CREATE TABLE lab_observations (
            cadastre_hash TEXT,
            year INTEGER,
            humus REAL,
            phosphorus_pentoxide REAL,
            potassium REAL,
            nitrate REAL,
            hydrogen REAL
        )');
        $pdo->exec("INSERT INTO parcel_geometries VALUES ('field', 72.735898, 40.645778)");
        $pdo->exec("INSERT INTO lab_observations VALUES ('field', 2026, 1.1, 27, 144, NULL, NULL)");
    }

    private function seedSoilgridsDatabase(): void
    {
        $pdo = new PDO('sqlite:'.$this->soilgridsDatabase);
        $pdo->exec('CREATE TABLE grid_cells (
            centroid_lat REAL,
            centroid_lng REAL,
            theta_fc REAL,
            theta_wp REAL,
            bulk_density REAL,
            texture_class TEXT
        )');
        $pdo->exec("INSERT INTO grid_cells VALUES (40.645778, 72.735898, 0.294, 0.166, 1.44, 'loam')");
    }
}
