<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\General\Filament\Resources\TermsDocumentResource\Pages;
use Modules\General\Models\TermsDocument;

class TermsDocumentResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = TermsDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Terms & Offers';

    protected static ?string $navigationGroup = NavigationGroup::Content->value;

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Document';

    protected static ?string $pluralModelLabel = 'Terms & Offers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'term' => 'Terms of Service',
                        'offer' => 'Public Offer',
                    ])
                    ->required()
                    ->disabledOn('edit'),
                Forms\Components\Select::make('locale')
                    ->options([
                        'en' => 'English',
                        'ru' => 'Russian',
                        'uz' => 'Uzbek',
                    ])
                    ->required()
                    ->disabledOn('edit'),
                Forms\Components\MarkdownEditor::make('content')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'term' => 'Terms of Service',
                        'offer' => 'Public Offer',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'term' => 'success',
                        'offer' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('locale')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                Tables\Columns\TextColumn::make('preview')
                    ->label('Content Preview')
                    ->wrap()
                    ->limit(100),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No documents found')
            ->emptyStateDescription('Create your first terms or offer document.');
    }

    public static function getEloquentQuery(): Builder
    {
        TermsDocument::syncFromDisk();

        return parent::getEloquentQuery()
            ->orderBy('type')
            ->orderBy('locale');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTermsDocuments::route('/'),
            'create' => Pages\CreateTermsDocument::route('/create'),
            'edit' => Pages\EditTermsDocument::route('/{record}/edit'),
        ];
    }
}
