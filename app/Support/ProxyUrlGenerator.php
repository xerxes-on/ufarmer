<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class ProxyUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        return route('media.proxy', $this->media);
    }
}
