<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Support\AdminActivityContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Crops\Filament\Resources\CropResource;
use Modules\Crops\Filament\Resources\ParentCropResource;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\ParentCrop;
use Tests\TestCase;

final class CropAdminWriteProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('parent_crops', function (Blueprint $table): void {
            $table->id();
            $table->json('name')->nullable();
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('crops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_crop_id')->nullable();
            $table->json('name')->nullable();
            $table->json('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        app(AdminActivityContext::class)->deactivate();

        Schema::dropIfExists('crops');
        Schema::dropIfExists('parent_crops');

        parent::tearDown();
    }

    public function test_crop_and_parent_crop_creation_routes_are_not_available(): void
    {
        $this->assertFalse(CropResource::canCreate());
        $this->assertFalse(ParentCropResource::canCreate());
        $this->assertArrayNotHasKey('create', CropResource::getPages());
        $this->assertArrayNotHasKey('create', ParentCropResource::getPages());
    }

    public function test_admin_context_blocks_crop_and_parent_crop_creation_at_model_boundary(): void
    {
        app(AdminActivityContext::class)->activate();

        try {
            Crop::query()->create(['name' => ['uz' => 'Blocked crop']]);
            $this->fail('Crop creation was not blocked.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('record', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        ParentCrop::query()->create(['name' => ['uz' => 'Blocked parent']]);
    }

    public function test_admin_context_blocks_reactivating_inactive_crops_and_parents(): void
    {
        $parentId = DB::table('parent_crops')->insertGetId([
            'name' => json_encode(['uz' => 'Inactive parent'], JSON_THROW_ON_ERROR),
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cropId = DB::table('crops')->insertGetId([
            'parent_crop_id' => $parentId,
            'name' => json_encode(['uz' => 'Inactive crop'], JSON_THROW_ON_ERROR),
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AdminActivityContext::class)->activate();

        try {
            Crop::query()->findOrFail($cropId)->update(['is_active' => true]);
            $this->fail('Crop reactivation was not blocked.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('is_active', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        ParentCrop::query()->findOrFail($parentId)->update(['is_active' => true]);
    }
}
