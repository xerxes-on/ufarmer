<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources\TermsDocumentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\General\Filament\Resources\LegalConfigResource;
use Modules\General\Filament\Resources\TermsDocumentResource;
use Modules\General\Models\TermsDocument;

class ListTermsDocuments extends ListRecords
{
    protected static string $resource = TermsDocumentResource::class;

    public function mount(): void
    {
        TermsDocument::syncFromDisk();

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('legalSettings')
                ->label('Legal Settings')
                ->icon('heroicon-o-adjustments-horizontal')
                ->url(fn (): string => LegalConfigResource::getUrl('index')),
        ];
    }
}
