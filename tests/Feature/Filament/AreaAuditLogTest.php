<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Support\AdminActivityContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\Area;
use Modules\Core\Models\User;
use Tests\TestCase;

class AreaAuditLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name');
            $table->decimal('area', 12, 2)->nullable();
            $table->boolean('irrigated')->default(false);
            $table->string('ownership_type')->nullable();
            $table->text('coordinates')->nullable();
            $table->foreignId('region_id')->nullable();
            $table->foreignId('area_type_id')->nullable();
            $table->foreignId('water_source_type_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_area_edits_record_old_and_new_values_with_the_editor(): void
    {
        $user = User::query()->create(['name' => 'Admin']);
        $this->actingAs($user);

        $area = Area::query()->create([
            'user_id' => $user->id,
            'name' => 'North Field',
            'area' => 5,
            'ownership_type' => 'owned',
        ]);

        app(AdminActivityContext::class)->activate();
        $area->update(['area' => 6]);
        app(AdminActivityContext::class)->deactivate();

        $activity = DB::table('activity_log')->sole();
        $changes = json_decode($activity->attribute_changes, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('areas', $activity->log_name);
        $this->assertSame('updated', $activity->event);
        $this->assertSame(Area::class, $activity->subject_type);
        $this->assertSame($area->id, $activity->subject_id);
        $this->assertSame(User::class, $activity->causer_type);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertEquals(5, $changes['old']['area']);
        $this->assertEquals(6, $changes['attributes']['area']);
    }
}
