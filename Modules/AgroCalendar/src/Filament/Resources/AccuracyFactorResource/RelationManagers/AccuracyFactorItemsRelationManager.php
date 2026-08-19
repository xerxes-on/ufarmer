<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\AgroCalendar\Models\AccuracyFactorItem;

final class AccuracyFactorItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.items');
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name.uz')
                ->label('Name (Uzbek)')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('name.en')
                ->label('Name (English)')
                ->maxLength(255),
            Forms\Components\TextInput::make('name.ru')
                ->label('Name (Russian)')
                ->maxLength(255),
            Forms\Components\Textarea::make('description.uz')
                ->label('Description (Uzbek)')
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('description.ru')
                ->label('Description (Russian)')
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('description.en')
                ->label('Description (English)')
                ->rows(2)
                ->columnSpanFull(),
            Forms\Components\Toggle::make('use_link')
                ->label('Use external link')
                ->dehydrated(false)
                ->live()
                ->afterStateHydrated(function (Forms\Components\Toggle $component, ?AccuracyFactorItem $record): void {
                    $component->state((bool) $record?->link);
                })
                ->columnSpanFull(),
            Forms\Components\TextInput::make('link')
                ->label('External Link')
                ->url()
                ->maxLength(2048)
                ->visible(fn (Forms\Get $get): bool => (bool) $get('use_link'))
                ->columnSpanFull(),
            SpatieMediaLibraryFileUpload::make('attachment')
                ->collection(AccuracyFactorItem::MEDIA_COLLECTION_ATTACHMENT)
                ->label('Attachment')
                ->disk(config('media-library.disk_name', 'public'))
                ->visibility('public')
                ->maxSize(10240)
                ->visible(fn (Forms\Get $get): bool => ! (bool) $get('use_link'))
                ->columnSpanFull(),
            Forms\Components\TextInput::make('order')
                ->label('Order')
                ->numeric()
                ->default(0),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (AccuracyFactorItem $record) => $record->getTranslation('name', 'uz'))
                    ->wrap(),
                Tables\Columns\IconColumn::make('has_attachment')
                    ->label('Attachment')
                    ->getStateUsing(fn (AccuracyFactorItem $record) => $record->link || $record->getFirstMedia(AccuracyFactorItem::MEDIA_COLLECTION_ATTACHMENT))
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order');
    }
}
