<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Crops\Filament\Resources\DrugResource\Pages;
use Modules\Crops\Models\Drug;
use Modules\Crops\Models\DrugCategory;

class DrugResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Drug::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Drugs';

    protected static ?string $navigationGroup = NavigationGroup::CropProtection->value;

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Drug Information')
                    ->schema([
                        Forms\Components\Select::make('drug_category_id')
                            ->label('Category')
                            ->relationship('drugCategory', 'name')
                            ->getOptionLabelFromRecordUsing(fn (DrugCategory $record) => $record->getTranslation('name', 'uz'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name.uz')
                            ->label('Name (Uzbek)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name.ru')
                            ->label('Name (Russian)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name.en')
                            ->label('Name (English)')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description.uz')
                            ->label('Description (Uzbek)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description.ru')
                            ->label('Description (Russian)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description.en')
                            ->label('Description (English)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Details')
                    ->schema([
                        Forms\Components\Textarea::make('details')
                            ->label('Details')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_deleted')
                            ->label('Deleted')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (Drug $record) => $record->getTranslation('name', 'uz'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("name->>'uz' {$direction}");
                    }),
                Tables\Columns\TextColumn::make('drugCategory.name')
                    ->label('Category')
                    ->getStateUsing(fn (Drug $record) => $record->drugCategory?->getTranslation('name', 'uz'))
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\IconColumn::make('is_deleted')
                    ->label('Deleted')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
                Tables\Filters\SelectFilter::make('drug_category_id')
                    ->label('Category')
                    ->relationship('drugCategory', 'name')
                    ->getOptionLabelFromRecordUsing(fn (DrugCategory $record) => $record->getTranslation('name', 'uz')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('drugCategory');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrugs::route('/'),
            'create' => Pages\CreateDrug::route('/create'),
            'edit' => Pages\EditDrug::route('/{record}/edit'),
        ];
    }
}
