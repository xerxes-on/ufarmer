<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\AgroCalendar\Enums\AccuracyFactorAction;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource\Pages;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorResource\RelationManagers;
use Modules\AgroCalendar\Models\AccuracyFactor;

class AccuracyFactorResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AccuracyFactor::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Accuracy Factors';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('Programmatic identifier (e.g. template, soil_analysis)'),
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
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection(AccuracyFactor::MEDIA_COLLECTION_IMAGE)
                            ->label('Image')
                            ->image()
                            ->disk(config('media-library.disk_name', 'public'))
                            ->visibility('public')
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Select::make('action')
                            ->label('Action')
                            ->options(AccuracyFactorAction::class)
                            ->helperText('Mobile app navigation target when user taps the factor'),
                        Forms\Components\TextInput::make('boost_pct')
                            ->label('Boost %')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('order')
                            ->label('Order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->circular()
                    ->getStateUsing(fn (AccuracyFactor $record) => $record->getFirstMediaUrl(AccuracyFactor::MEDIA_COLLECTION_IMAGE) ?: null),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (AccuracyFactor $record) => $record->getTranslation('name', 'uz'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (?AccuracyFactorAction $state): string => match ($state) {
                        AccuracyFactorAction::Analysis => 'success',
                        AccuracyFactorAction::Scanner => 'info',
                        AccuracyFactorAction::Marketplace => 'warning',
                        AccuracyFactorAction::FieldHistory => 'primary',
                        default => 'gray',
                    })
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('boost_pct')
                    ->label('Boost %')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('order')
            ->defaultSort('order');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AccuracyFactorItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccuracyFactors::route('/'),
            'create' => Pages\CreateAccuracyFactor::route('/create'),
            'edit' => Pages\EditAccuracyFactor::route('/{record}/edit'),
        ];
    }
}
