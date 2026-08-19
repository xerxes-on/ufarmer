<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\ArticleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\General\Filament\Resources\ArticleResource;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;
}
