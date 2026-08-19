<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\ArticleTagResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\General\Filament\Resources\ArticleTagResource;

class ListArticleTags extends ListRecords
{
    protected static string $resource = ArticleTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
