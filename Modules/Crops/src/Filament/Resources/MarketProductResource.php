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
use Modules\Crops\Filament\Resources\MarketProductResource\Pages;
use Modules\Crops\Filament\Resources\MarketProductResource\RelationManagers;
use Modules\Crops\Models\MarketProduct;
use Modules\Crops\Models\MarketProductCategory;
use Modules\Crops\Models\MarketProductUnit;

class MarketProductResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = MarketProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Market Products';

    protected static ?string $navigationGroup = NavigationGroup::Catalog->value;

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(fn () => MarketProductCategory::query()
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn ($item) => [
                                    $item->id => $item->getTranslatedName('uz'),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->options(fn () => MarketProductUnit::query()
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn ($item) => [
                                    $item->id => $item->getTranslatedName('uz'),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'drug' => 'Drug (Pesticide/Herbicide)',
                                'fertilizer' => 'Fertilizer',
                                'seed' => 'Seed',
                            ])
                            ->required(),

                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options([
                                'erp' => 'ERP System',
                                'seller_proposal' => 'Seller Proposal',
                                'manual' => 'Manual Entry',
                            ])
                            ->default('manual')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Image')
                    ->schema([
                        Forms\Components\TextInput::make('image')
                            ->label('Image URL')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Enter an external image URL')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('External Reference')
                    ->schema([
                        Forms\Components\TextInput::make('external_id')
                            ->label('External ID')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('ID from ERP system (read-only)'),
                    ])
                    ->collapsed()
                    ->visible(fn (?MarketProduct $record) => $record?->external_id !== null),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular()
                    ->extraImgAttributes(['loading' => 'lazy']),
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->getStateUsing(fn (MarketProduct $record) => $record->category?->getTranslatedName('uz'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->getStateUsing(fn (MarketProduct $record) => $record->unit?->getTranslatedName('uz'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'drug' => 'danger',
                        'fertilizer' => 'success',
                        'seed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'erp' => 'primary',
                        'seller_proposal' => 'warning',
                        'manual' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('treatments_count')
                    ->label('Treatments')
                    ->counts('treatments')
                    ->badge()
                    ->color('info'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'drug' => 'Drug',
                        'fertilizer' => 'Fertilizer',
                        'seed' => 'Seed',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'erp' => 'ERP System',
                        'seller_proposal' => 'Seller Proposal',
                        'manual' => 'Manual Entry',
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => MarketProductCategory::query()
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($item) => [
                            $item->id => $item->getTranslatedName('uz'),
                        ])
                        ->toArray()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TreatmentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'unit']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketProducts::route('/'),
            'create' => Pages\CreateMarketProduct::route('/create'),
            'view' => Pages\ViewMarketProduct::route('/{record}'),
            'edit' => Pages\EditMarketProduct::route('/{record}/edit'),
        ];
    }
}
