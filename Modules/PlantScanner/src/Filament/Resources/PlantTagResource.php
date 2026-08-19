<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PlantScanner\Filament\Resources\PlantTagResource\Pages;
use Modules\PlantScanner\Models\PlantTag;

class PlantTagResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = PlantTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Plant Tags';

    protected static ?string $navigationGroup = NavigationGroup::Diagnostics->value;

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tag Information')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_uz')
                            ->label('Name (Uzbek)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_ru')
                            ->label('Name (Russian)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label('Name (English)')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description_uz')
                            ->label('Description (Uzbek)')
                            ->rows(2),
                        Forms\Components\Textarea::make('description_ru')
                            ->label('Description (Russian)')
                            ->rows(2),
                        Forms\Components\Textarea::make('description_en')
                            ->label('Description (English)')
                            ->rows(2),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('name_uz')
                    ->label('Name (UZ)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_ru')
                    ->label('Name (RU)')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name (EN)')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('plant_details_count')
                    ->label('Plants')
                    ->counts('plantDetails')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name_uz');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlantTags::route('/'),
            'create' => Pages\CreatePlantTag::route('/create'),
            'edit' => Pages\EditPlantTag::route('/{record}/edit'),
        ];
    }
}
