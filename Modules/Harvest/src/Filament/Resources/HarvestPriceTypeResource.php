<?php

declare(strict_types=1);

namespace Modules\Harvest\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Harvest\Filament\Resources\HarvestPriceTypeResource\Pages;
use Modules\Harvest\Models\HarvestPriceType;

class HarvestPriceTypeResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = HarvestPriceType::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Price Types';

    protected static ?string $navigationGroup = NavigationGroup::MarketplaceAndPrices->value;

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Price Type';

    protected static ?string $pluralModelLabel = 'Price Types';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30)
                            ->helperText('Unique identifier (e.g., fixed, negotiable)'),
                    ]),

                Forms\Components\Section::make('Names')
                    ->schema([
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
                            ->rows(2),

                        Forms\Components\Textarea::make('description.ru')
                            ->label('Description (Russian)')
                            ->rows(2),

                        Forms\Components\Textarea::make('description.en')
                            ->label('Description (English)')
                            ->rows(2),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Display Settings')
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (HarvestPriceType $record) => $record->getTranslation('name', 'uz'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('harvests_count')
                    ->label('Harvests')
                    ->counts('harvests')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
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
            'index' => Pages\ListHarvestPriceTypes::route('/'),
            'create' => Pages\CreateHarvestPriceType::route('/create'),
            'edit' => Pages\EditHarvestPriceType::route('/{record}/edit'),
        ];
    }
}
