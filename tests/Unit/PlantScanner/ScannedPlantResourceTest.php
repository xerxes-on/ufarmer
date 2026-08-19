<?php

declare(strict_types=1);

namespace Tests\Unit\PlantScanner;

use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Modules\PlantScanner\Enums\ScanStatus;
use Modules\PlantScanner\Filament\Resources\ScannedPlantResource;
use Modules\PlantScanner\Filament\Resources\ScannedPlantResource\Pages\ListScannedPlants;
use Modules\PlantScanner\Models\PlantDetail;
use Modules\PlantScanner\Models\ScannedPlant;
use ReflectionMethod;
use Tests\TestCase;

final class ScannedPlantResourceTest extends TestCase
{
    public function test_the_resource_is_strictly_read_only(): void
    {
        $scan = new ScannedPlant;

        $this->assertFalse(ScannedPlantResource::canCreate());
        $this->assertFalse(ScannedPlantResource::canEdit($scan));
        $this->assertFalse(ScannedPlantResource::canDelete($scan));
        $this->assertFalse(ScannedPlantResource::canDeleteAny());
        $this->assertFalse(ScannedPlantResource::canForceDelete($scan));
        $this->assertFalse(ScannedPlantResource::canForceDeleteAny());
        $this->assertFalse(ScannedPlantResource::canReplicate($scan));
        $this->assertFalse(ScannedPlantResource::canRestore($scan));
        $this->assertFalse(ScannedPlantResource::canRestoreAny());
        $this->assertFalse(ScannedPlantResource::canReorder());
    }

    public function test_the_scan_list_has_one_filtering_tab_for_each_supported_mode(): void
    {
        $tabs = (new ListScannedPlants)->getTabs();

        $this->assertSame(['recognition', 'pests', 'diagnosis', 'not_identified'], array_keys($tabs));

        foreach (array_intersect_key($tabs, array_flip(['recognition', 'pests', 'diagnosis'])) as $mode => $tab) {
            $query = $tab->modifyQuery(ScannedPlant::query());

            $this->assertStringContainsString('"scan_mode" = ?', $query->toSql());
            $this->assertSame([$mode], $query->getBindings());
        }

        $notIdentifiedQuery = $tabs['not_identified']->modifyQuery(ScannedPlant::query());

        $this->assertStringContainsString('"status" = ?', $notIdentifiedQuery->toSql());
        $this->assertStringContainsString('"plant_detail_id" is null', $notIdentifiedQuery->toSql());
        $this->assertStringContainsString('"pest_detail_id" is null', $notIdentifiedQuery->toSql());
        $this->assertStringContainsString('"identified_disease_name" is null', $notIdentifiedQuery->toSql());
        $this->assertSame('completed', $notIdentifiedQuery->getBindings()[0]);
    }

    public function test_the_admin_read_model_exposes_multilingual_results_and_image_evidence(): void
    {
        config()->set('app.url', 'https://admin.ufarmer.test');
        config()->set('plantscanner.image.optimized_disk', null);
        config()->set('filesystems.default', 'public');
        Storage::fake('public');

        $scan = new ScannedPlant([
            'scan_mode' => 'recognition',
            'optimized_image_path' => 'plant-scans/optimized/user.jpg',
            'metadata' => [
                'unsplash_images' => [
                    ['url' => 'https://images.example.test/reference-2.jpg'],
                ],
            ],
        ]);
        $scan->setRelation('plantDetail', new PlantDetail([
            'scientific_name' => 'Solanum lycopersicum',
            'common_name_en' => 'Tomato',
            'common_name_ru' => 'Томат',
            'common_name_uz' => 'Pomidor',
            'image_url' => 'https://images.example.test/reference-1.jpg',
            'gallery_images' => [
                ['url' => 'https://images.example.test/reference-1.jpg'],
            ],
        ]));

        $this->assertSame([
            'en' => 'Tomato',
            'ru' => 'Томат',
            'uz' => 'Pomidor',
        ], $scan->resultNames());
        $this->assertSame('Solanum lycopersicum', $scan->resultScientificName());
        $this->assertStringEndsWith('/storage/plant-scans/optimized/user.jpg', (string) $scan->uploadedImageUrl());
        $this->assertSame([
            'https://images.example.test/reference-1.jpg',
            'https://images.example.test/reference-2.jpg',
        ], $scan->referenceImageUrls());
    }

    public function test_nested_payloads_are_serialized_before_filament_escapes_them(): void
    {
        $method = new ReflectionMethod(ScannedPlantResource::class, 'formatJson');
        $formatted = $method->invoke(null, [
            'nested' => ['name' => ['en' => 'Tomato', 'ru' => 'Томат']],
        ]);

        $this->assertIsString($formatted);
        $this->assertStringContainsString('"nested": {', $formatted);
        $this->assertStringContainsString('"ru": "Томат"', $formatted);
    }

    public function test_the_infolist_renders_nested_scanner_payloads_without_a_view_exception(): void
    {
        $scan = new ScannedPlant([
            'status' => ScanStatus::Completed,
            'scan_mode' => 'recognition',
            'optimized_image_path' => 'plant-scans/optimized/user.jpg',
            'structured_data' => ['plant' => ['name' => ['en' => 'Tomato']]],
            'metadata' => ['images' => [['url' => 'https://images.example.test/tomato.jpg']]],
            'ai_enriched_data' => ['care' => ['watering' => ['en' => 'Moderate']]],
            'ai_usage_details' => ['entries' => []],
        ]);
        $livewire = new class extends Component implements HasInfolists
        {
            use InteractsWithInfolists;

            public function render(): string
            {
                return '';
            }
        };

        $html = ScannedPlantResource::infolist(
            Infolist::make($livewire)->name('scan')->record($scan)
        )->toHtml();

        $this->assertStringContainsString('&quot;plant&quot;:', $html);
        $this->assertStringContainsString('&quot;watering&quot;:', $html);
        $this->assertStringContainsString('&quot;images&quot;:', $html);
        $this->assertStringContainsString('data-json-viewer', $html);
        $this->assertStringContainsString('previewUploadedImage', $html);
        $this->assertStringContainsString('cursor-zoom-in', $html);

        $previewHtml = view('filament.infolists.image-preview', [
            'url' => $scan->uploadedImageUrl(),
            'alt' => 'User uploaded plant scan image',
        ])->render();

        $this->assertStringContainsString('max-h-[78vh]', $previewHtml);
        $this->assertStringContainsString('plant-scans/optimized/user.jpg', $previewHtml);
    }
}
