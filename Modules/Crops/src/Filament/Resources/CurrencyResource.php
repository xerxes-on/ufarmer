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
use Modules\Crops\Filament\Resources\CurrencyResource\Pages;
use Modules\Crops\Models\Currency;

class CurrencyResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Currencies';

    protected static ?string $navigationGroup = NavigationGroup::Catalog->value;

    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Currency Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code (e.g., UZS, USD)')
                            ->required()
                            ->maxLength(10),
                        Forms\Components\TextInput::make('symbol')
                            ->label('Symbol')
                            ->maxLength(10),
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

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbol'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'uz'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('crop_prices_count')
                    ->label('Prices')
                    ->counts('cropPrices')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
