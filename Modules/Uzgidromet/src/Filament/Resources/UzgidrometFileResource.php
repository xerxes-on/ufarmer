<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Uzgidromet\Filament\Resources\UzgidrometFileResource\Pages;
use Modules\Uzgidromet\Models\UzgidrometFile;

class UzgidrometFileResource extends Resource
{
    protected static ?string $model = UzgidrometFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?int $navigationSort = 1;

    protected const string DISK = 's3';

    protected const string DIRECTORY = 'uzgidromet/prognoses';

    protected const int MAX_SIZE_KB = 20480; // 20 MB

    /**
     * @var array<int, string>
     */
    protected const array ACCEPTED_MIME_TYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public static function getNavigationGroup(): ?string
    {
        return __('uzgidromet::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('uzgidromet::filament.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('uzgidromet::filament.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('uzgidromet::filament.plural_model_label');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('uzgidromet::filament.form.section_title'))
                ->description(__('uzgidromet::filament.form.section_description'))
                ->schema([
                    FileUpload::make('file_path')
                        ->label(__('uzgidromet::filament.form.file'))
                        ->required()
                        ->disk(self::DISK)
                        ->directory(self::DIRECTORY)
                        ->preserveFilenames()
                        ->acceptedFileTypes(self::ACCEPTED_MIME_TYPES)
                        ->maxSize(self::MAX_SIZE_KB)
                        ->helperText(__('uzgidromet::filament.form.helper'))
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, Forms\Set $set): string {
                            $originalName = $file->getClientOriginalName();
                            $mimeType = $file->getMimeType() ?? 'application/octet-stream';
                            $size = (int) $file->getSize();

                            $directory = self::DIRECTORY;
                            $storedName = now()->format('YmdHis').'_'.$originalName;

                            $path = $file->storeAs($directory, $storedName, ['disk' => self::DISK]);

                            $set('original_name', $originalName);
                            $set('mime_type', $mimeType);
                            $set('file_size_bytes', $size);

                            return is_string($path) ? $path : ($directory.'/'.$storedName);
                        })
                        ->columnSpanFull()
                        ->disabledOn('edit'),

                    Forms\Components\Hidden::make('original_name'),
                    Forms\Components\Hidden::make('mime_type'),
                    Forms\Components\Hidden::make('file_size_bytes'),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label(__('uzgidromet::filament.table.filename'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label(__('uzgidromet::filament.table.type'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'application/pdf' => 'PDF',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('file_size_human')
                    ->label(__('uzgidromet::filament.table.size'))
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('file_size_bytes', $direction)),
                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label(__('uzgidromet::filament.table.uploaded_by'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('uzgidromet::filament.table.uploaded_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUzgidrometFiles::route('/'),
            'create' => Pages\CreateUzgidrometFile::route('/create'),
            'edit' => Pages\EditUzgidrometFile::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('uploadedBy');
    }

    protected static function userHasAccess(): bool
    {
        return auth()->check();
    }

    public static function canViewAny(): bool
    {
        return self::userHasAccess();
    }

    public static function canCreate(): bool
    {
        return self::userHasAccess();
    }

    public static function canEdit($record): bool
    {
        return self::userHasAccess();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
