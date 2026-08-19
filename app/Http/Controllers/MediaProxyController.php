<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaProxyController extends Controller
{
    public function __invoke(Media $media): StreamedResponse|Response
    {
        $disk = Storage::disk($media->disk);

        $path = $media->getPathRelativeToRoot();

        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->stream(
            fn () => fpassthru($disk->readStream($path)),
            200,
            [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
                'Cache-Control' => 'public, max-age=86400',
            ]
        );
    }
}
