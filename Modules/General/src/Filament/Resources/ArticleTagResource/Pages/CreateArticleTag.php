<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\ArticleTagResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\General\Filament\Resources\ArticleTagResource;

class CreateArticleTag extends CreateRecord
{
    protected static string $resource = ArticleTagResource::class;
}
