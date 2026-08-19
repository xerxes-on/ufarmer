<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\ArticleTagResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\General\Filament\Resources\ArticleTagResource;

class EditArticleTag extends EditRecord
{
    protected static string $resource = ArticleTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
