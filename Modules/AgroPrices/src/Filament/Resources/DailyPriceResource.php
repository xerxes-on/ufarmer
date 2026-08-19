<?php

declare(strict_types=1);

namespace Modules\AgroPrices\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\AgroPrices\Enums\PriceStatus;
use Modules\AgroPrices\Filament\Resources\DailyPriceResource\Pages;
use Modules\AgroPrices\Models\DailyPrice;
use Modules\AgroPrices\Models\Product;

class DailyPriceResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = DailyPrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Daily Prices';

    protected static ?string $navigationGroup = NavigationGroup::MarketplaceAndPrices->value;

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('product');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Price Information')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Product $record) => $record->getTranslation('name', 'uz'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->prefix('UZS'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(collect(PriceStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->name]))
                            ->required()
                            ->default(PriceStatus::Stable->value),
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
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->getStateUsing(fn ($record) => $record->product?->getTranslation('name', 'uz'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('UZS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => PriceStatus::from($state)->color()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Product $record) => $record->getTranslation('name', 'uz'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(PriceStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->name])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->paginationPageOptions([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyPrices::route('/'),
            'create' => Pages\CreateDailyPrice::route('/create'),
            'edit' => Pages\EditDailyPrice::route('/{record}/edit'),
        ];
    }
}
