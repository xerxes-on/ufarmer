<?php

declare(strict_types=1);

namespace Modules\Crops\Filament\Resources;

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
use Modules\Crops\Filament\Resources\WeedResource\Pages;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\Weed;

class WeedResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Weed::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Weeds';

    protected static ?string $navigationGroup = NavigationGroup::CropProtection->value;

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Weed Information')
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
                        SpatieMediaLibraryFileUpload::make('image')
                            ->collection(Weed::MEDIA_COLLECTION_IMAGE)
                            ->label('Weed Image')
                            ->image()
                            ->imageEditor()
                            ->disk(config('media-library.disk_name', 'public'))
                            ->visibility('public')
                            ->maxSize(10240)
                            ->helperText('Upload a weed image (max 10MB)')
                            ->columnSpanFull(),
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
                        Forms\Components\Textarea::make('details.uz')
                            ->label('Details (Uzbek)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('details.ru')
                            ->label('Details (Russian)')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('details.en')
                            ->label('Details (English)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

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

                Forms\Components\Section::make('Associated Crops')
                    ->schema([
                        Forms\Components\Select::make('crops')
                            ->options(fn () => Crop::query()
                                ->select('crops.id')
                                ->selectRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') as name_label")
                                ->orderByRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en')")
                                ->limit(50)
                                ->pluck('name_label', 'id')
                                ->toArray())
                            ->getSearchResultsUsing(fn (string $search) => Crop::query()
                                ->select('crops.id')
                                ->selectRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') as name_label")
                                ->whereRaw(
                                    "COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') ILIKE ?",
                                    ["%{$search}%"]
                                )
                                ->orderByRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en')")
                                ->limit(50)
                                ->pluck('name_label', 'id')
                                ->toArray())
                            ->getOptionLabelsUsing(fn (array $values) => Crop::query()
                                ->select('crops.id')
                                ->selectRaw("COALESCE(crops.name->>'uz', crops.name->>'ru', crops.name->>'en') as name_label")
                                ->whereIn('crops.id', $values)
                                ->pluck('name_label', 'id')
                                ->toArray())
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->dehydrated(false)
                            ->loadStateFromRelationshipsUsing(function (Forms\Components\Select $component, Weed $record): void {
                                $component->state(
                                    $record->crops()
                                        ->select('crops.id')
                                        ->pluck('crops.id')
                                        ->map(static fn ($id): string => (string) $id)
                                        ->all()
                                );
                            })
                            ->saveRelationshipsUsing(function (Forms\Components\Select $component, Weed $record, ?array $state): void {
                                $ids = collect($state ?? [])
                                    ->map(static fn ($id): int => (int) $id)
                                    ->filter()
                                    ->values()
                                    ->all();

                                $record->crops()->sync($ids);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular()
                    ->extraImgAttributes(['loading' => 'lazy']),
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => $record->getTranslation('name', 'uz'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name->uz', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("name->>'uz' {$direction}");
                    }),
                Tables\Columns\TextColumn::make('crops_count')
                    ->label('Crops')
                    ->counts('crops')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('treatments_count')
                    ->label('Treatments')
                    ->counts('treatments')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
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
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('media');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeeds::route('/'),
            'create' => Pages\CreateWeed::route('/create'),
            'view' => Pages\ViewWeed::route('/{record}'),
            'edit' => Pages\EditWeed::route('/{record}/edit'),
        ];
    }
}
