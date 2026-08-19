<?php

declare(strict_types=1);

namespace Tests\Unit;

use Filament\Forms\Form;
use Modules\Crops\Filament\Resources\CropResource;
use Modules\Crops\Filament\Resources\CropResource\Pages\EditCrop;
use Modules\Crops\Filament\Resources\ParentCropResource\RelationManagers\CropsRelationManager;
use Modules\Crops\Models\Crop;
use ReflectionClass;
use Tests\TestCase;

class CropRelationManagersTest extends TestCase
{
    public function test_crop_edit_relation_managers_have_matching_model_relationships(): void
    {
        foreach (CropResource::getRelations() as $relationManager) {
            $relationship = (new ReflectionClass($relationManager))
                ->getProperty('relationship')
                ->getValue();

            $this->assertTrue(
                method_exists(Crop::class, $relationship),
                "{$relationManager} requires Crop::{$relationship}().",
            );
        }
    }

    public function test_crop_edit_form_excludes_columns_dropped_from_the_shared_schema(): void
    {
        $form = CropResource::form(Form::make(new EditCrop));

        $this->assertArrayNotHasKey('product_group_slug', $form->getFlatFields(true));
    }

    public function test_parent_crop_child_form_excludes_columns_dropped_from_the_shared_schema(): void
    {
        $relationManager = (new ReflectionClass(CropsRelationManager::class))->newInstanceWithoutConstructor();
        $form = $relationManager->form(Form::make(new EditCrop));

        $this->assertArrayNotHasKey('recommended_temp_min', $form->getFlatFields(true));
        $this->assertArrayNotHasKey('recommended_temp_max', $form->getFlatFields(true));
    }
}
