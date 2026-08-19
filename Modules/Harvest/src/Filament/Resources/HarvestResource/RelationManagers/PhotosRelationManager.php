<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources\HarvestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.photos');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('path')
                    ->label('Path')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('disk')
                    ->label('Disk')
                    ->default('s3')
                    ->maxLength(50),

                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'harvest' => 'Harvest',
                        'quality' => 'Quality',
                        'field' => 'Field',
                    ])
                    ->default('harvest'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),

                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Photo')
                    ->circular(false)
                    ->size(80),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'harvest' => 'success',
                        'quality' => 'info',
                        'field' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('disk')
                    ->label('Disk'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'harvest' => 'Harvest',
                        'quality' => 'Quality',
                        'field' => 'Field',
                    ]),
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
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
