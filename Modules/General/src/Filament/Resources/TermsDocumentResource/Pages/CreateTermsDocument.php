<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\TermsDocumentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\General\Filament\Resources\TermsDocumentResource;
use Modules\General\Models\TermsDocument;

class CreateTermsDocument extends CreateRecord
{
    protected static string $resource = TermsDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id'] = TermsDocument::buildId($data['type'], $data['locale']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
