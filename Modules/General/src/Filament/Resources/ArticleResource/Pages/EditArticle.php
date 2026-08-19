<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\ArticleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\General\Filament\Resources\ArticleResource;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;
}
