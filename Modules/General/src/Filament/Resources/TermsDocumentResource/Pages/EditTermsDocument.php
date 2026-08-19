<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\TermsDocumentResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\General\Filament\Resources\TermsDocumentResource;
use Modules\General\Models\TermsDocument;

class EditTermsDocument extends EditRecord
{
    protected static string $resource = TermsDocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['id'] = TermsDocument::buildId($data['type'], $data['locale']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
