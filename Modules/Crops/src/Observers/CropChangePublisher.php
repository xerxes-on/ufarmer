<?php

declare(strict_types=1);

namespace Modules\Crops\Observers;

use App\Events\ModelChangedEvent;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\ParentCrop;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Publishes crop changes made through the admin panel.
 *
 * ufarm-api has its own Crop/ParentCrop observers, but admin-api — where the
 * Filament CRUD actually lives — had none, so every edit made in the panel
 * (name, description, active flag, image) was invisible to consumers such as
 * the LMS `lms.parent_crops` queue.
 *
 * Images need the extra Media hook: spatie/media-library writes to the `media`
 * table without touching the owning model, so an image-only change fires no
 * Crop::updated event at all. Observing Media covers that case.
 *
 * Payloads mirror ufarm-api's CropObserver/ParentCropObserver exactly, so
 * consumers need no change and it does not matter which app made the edit.
 */
final class CropChangePublisher
{
    /**
     * Media collections that carry a crop's public image, mapped to the
     * routing-key model name used by the existing crop observers.
     *
     * @var array<class-string, array{collection: string, model: string}>
     */
    private const WATCHED_MEDIA = [
        Crop::class => ['collection' => Crop::MEDIA_COLLECTION_IMAGE, 'model' => 'crop'],
        ParentCrop::class => ['collection' => ParentCrop::MEDIA_COLLECTION_IMAGE, 'model' => 'parent_crop'],
    ];

    public function cropSaved(Crop $crop): void
    {
        $this->dispatch('crop', $crop);
    }

    public function cropDeleted(Crop $crop): void
    {
        ModelChangedEvent::dispatch('crop', 'deleted', ['id' => $crop->id]);
    }

    public function parentCropSaved(ParentCrop $parentCrop): void
    {
        $this->dispatch('parent_crop', $parentCrop);
    }

    public function parentCropDeleted(ParentCrop $parentCrop): void
    {
        ModelChangedEvent::dispatch('parent_crop', 'deleted', ['id' => $parentCrop->id]);
    }

    /**
     * Media rows are written without touching the owning model, so an
     * image-only edit reaches consumers through here or not at all.
     */
    public function mediaChanged(Media $media): void
    {
        $watched = self::WATCHED_MEDIA[$media->model_type] ?? null;

        if ($watched === null || $media->collection_name !== $watched['collection']) {
            return;
        }

        $owner = $media->model_type::find($media->model_id);

        if ($owner === null) {
            return;
        }

        // Drop the cached collection so image_url reflects the post-change
        // state rather than what was loaded before this write.
        $owner->unsetRelation('media');

        $this->dispatch($watched['model'], $owner);
    }

    private function dispatch(string $model, Crop|ParentCrop $owner): void
    {
        ModelChangedEvent::dispatch($model, 'updated', [
            'id' => $owner->id,
            'name' => $owner->getTranslations('name'),
            'description' => $owner->getTranslations('description'),
            'is_active' => $owner->is_active,
            'image_url' => $owner->image_url,
        ]);
    }
}
