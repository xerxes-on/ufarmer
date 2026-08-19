<?php

declare(strict_types=1);

namespace Tests\Feature\Agronom;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Agronom\Support\DefaultAgronomProfileImage;
use Modules\Core\Models\AppSetting;
use Tests\TestCase;

final class DefaultAgronomProfileImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('value_type');
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->json('description')->nullable();
            $table->json('enum_options')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        AppSetting::query()->create([
            'key' => DefaultAgronomProfileImage::SETTING_KEY,
            'value_type' => AppSetting::TYPE_JSON,
            'value' => json_encode(['source' => 'builtin']),
            'group' => AppSetting::GROUP_AGRONOM,
        ]);
    }

    public function test_it_only_saves_a_path_that_exists_on_the_users_disk(): void
    {
        Storage::fake(DefaultAgronomProfileImage::STORAGE_DISK);
        Storage::disk(DefaultAgronomProfileImage::STORAGE_DISK)
            ->put('defaults/agronom/profile.jpg', 'image');

        $setting = app(DefaultAgronomProfileImage::class);

        $this->assertTrue($setting->setStoragePath('/defaults/agronom/profile.jpg'));
        $this->assertSame([
            'source' => 'storage',
            'path' => 'defaults/agronom/profile.jpg',
        ], $setting->value());
    }

    public function test_it_rejects_a_missing_upload_without_changing_the_setting(): void
    {
        Storage::fake(DefaultAgronomProfileImage::STORAGE_DISK);

        $setting = app(DefaultAgronomProfileImage::class);

        $this->assertFalse($setting->setStoragePath('defaults/agronom/missing.jpg'));
        $this->assertSame(['source' => 'builtin'], $setting->value());
    }
}
