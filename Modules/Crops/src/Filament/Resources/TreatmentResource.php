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
use Modules\Crops\Filament\Resources\TreatmentResource\Pages;
use Modules\Crops\Models\Crop;
use Modules\Crops\Models\Disease;
use Modules\Crops\Models\Drug;
use Modules\Crops\Models\MarketProduct;
use Modules\Crops\Models\Pest;
use Modules\Crops\Models\Treatment;
use Modules\Crops\Models\Weed;

class TreatmentResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Treatment::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Treatments';

    protected static ?string $navigationGroup = NavigationGroup::CropProtection->value;

    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Legacy Treatment')
                            ->schema([
                                Forms\Components\Section::make('Legacy Treatment Information')
                                    ->description('Original treatment format with Drug/Disease/Pest relationships')
                                    ->schema([
                                        Forms\Components\TextInput::make('type')
                                            ->label('Type')
                                            ->maxLength(32),
                                        Forms\Components\Select::make('disease_id')
                                            ->label('Disease')
                                            ->relationship('disease', 'name')
                                            ->getOptionLabelFromRecordUsing(fn (Disease $record) => $record->getTranslation('name', 'uz'))
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                        Forms\Components\Select::make('pest_id')
                                            ->label('Pest')
                                            ->relationship('pest', 'name')
                                            ->getOptionLabelFromRecordUsing(fn (Pest $record) => $record->getTranslation('name', 'uz'))
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                        Forms\Components\Select::make('drug_id')
                                            ->label('Drug')
                                            ->relationship('drug', 'name')
                                            ->getOptionLabelFromRecordUsing(fn (Drug $record) => $record->getTranslation('name', 'uz'))
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Legacy Dose')
                                    ->schema([
                                        Forms\Components\TextInput::make('dose_text')
                                            ->label('Dose Text')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('unit_external_id')
                                            ->label('Unit External ID')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('unit_label')
                                            ->label('Unit Label')
                                            ->maxLength(50),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Description')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Market Product Treatment')
                            ->schema([
                                Forms\Components\Section::make('Market Product Treatment')
                                    ->description('New treatment format linked to market products')
                                    ->schema([
                                        Forms\Components\Select::make('market_product_id')
                                            ->label('Market Product')
                                            ->options(fn () => MarketProduct::query()
                                                ->where('is_active', true)
                                                ->orderBy('name')
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                                ->toArray())
                                            ->getSearchResultsUsing(fn (string $search) => MarketProduct::query()
                                                ->where('name', 'ilike', "%{$search}%")
                                                ->where('is_active', true)
                                                ->orderBy('name')
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('problem_type')
                                            ->label('Problem Type')
                                            ->options([
                                                'disease' => 'Disease',
                                                'pest' => 'Pest',
                                                'weed' => 'Weed',
                                            ])
                                            ->nullable()
                                            ->reactive()
                                            ->afterStateUpdated(fn (Forms\Set $set) => $set('problem_id_poly', null)),

                                        Forms\Components\Select::make('problem_id_poly')
                                            ->label('Problem')
                                            ->options(function (Forms\Get $get) {
                                                $type = $get('problem_type');

                                                if ($type === 'disease') {
                                                    return Disease::query()
                                                        ->where('is_active', true)
                                                        ->get()
                                                        ->mapWithKeys(fn ($item) => [$item->id => $item->getTranslation('name', 'uz')])
                                                        ->toArray();
                                                }

                                                if ($type === 'pest') {
                                                    return Pest::query()
                                                        ->where('is_active', true)
                                                        ->get()
                                                        ->mapWithKeys(fn ($item) => [$item->id => $item->getTranslation('name', 'uz')])
                                                        ->toArray();
                                                }

                                                if ($type === 'weed') {
                                                    return Weed::query()
                                                        ->where('is_active', true)
                                                        ->get()
                                                        ->mapWithKeys(fn ($item) => [$item->id => $item->getTranslation('name', 'uz')])
                                                        ->toArray();
                                                }

                                                return [];
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->visible(fn (Forms\Get $get) => $get('problem_type') !== null),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Instructions')
                                    ->schema([
                                        Forms\Components\Textarea::make('instructions')
                                            ->label('Instructions')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->helperText('Treatment application instructions'),
                                    ]),

                                Forms\Components\Section::make('Associated Crops')
                                    ->schema([
                                        Forms\Components\Select::make('market_crops')
                                            ->label('Crops for Market Treatment')
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
                                            ->loadStateFromRelationshipsUsing(function (Forms\Components\Select $component, Treatment $record): void {
                                                $component->state(
                                                    $record->cropsFromMarket()
                                                        ->select('crops.id')
                                                        ->pluck('crops.id')
                                                        ->map(static fn ($id): string => (string) $id)
                                                        ->all()
                                                );
                                            })
                                            ->saveRelationshipsUsing(function (Forms\Components\Select $component, Treatment $record, ?array $state): void {
                                                $ids = collect($state ?? [])
                                                    ->map(static fn ($id): int => (int) $id)
                                                    ->filter()
                                                    ->values()
                                                    ->all();

                                                $record->cropsFromMarket()->sync($ids);
                                            }),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Section::make('Dose Information')
                    ->schema([
                        Forms\Components\TextInput::make('dose_min')
                            ->label('Dose Min')
                            ->numeric()
                            ->step(0.0001),
                        Forms\Components\TextInput::make('dose_max')
                            ->label('Dose Max')
                            ->numeric()
                            ->step(0.0001),
                        Forms\Components\TextInput::make('dose_unit')
                            ->label('Dose Unit')
                            ->maxLength(50)
                            ->placeholder('e.g., kg/ha, l/ha'),
                    ])
                    ->columns(3),

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
                Tables\Columns\TextColumn::make('treatment_type')
                    ->label('Type')
                    ->getStateUsing(function (Treatment $record): string {
                        if ($record->market_product_id !== null) {
                            return 'Market';
                        }

                        return $record->type ?? 'Legacy';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Market' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('product')
                    ->label('Product')
                    ->getStateUsing(function (Treatment $record): ?string {
                        if ($record->marketProduct !== null) {
                            return $record->marketProduct->name;
                        }

                        if ($record->drug !== null) {
                            return $record->drug->getTranslation('name', 'uz');
                        }

                        return null;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('marketProduct', fn (Builder $q) => $q->where('name', 'ilike', "%{$search}%"))
                                ->orWhereHas('drug', fn (Builder $q) => $q->whereRaw("name->>'uz' ILIKE ?", ["%{$search}%"]));
                        });
                    }),
                Tables\Columns\TextColumn::make('issue')
                    ->label('Issue')
                    ->getStateUsing(function (Treatment $record): ?string {
                        if ($record->problem_type !== null) {
                            $problem = $record->problem();

                            return $problem?->getTranslation('name', 'uz');
                        }

                        if ($record->disease !== null) {
                            return $record->disease->getTranslation('name', 'uz');
                        }

                        if ($record->pest !== null) {
                            return $record->pest->getTranslation('name', 'uz');
                        }

                        return null;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('problem_type')
                    ->label('Problem Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'disease' => 'danger',
                        'pest' => 'warning',
                        'weed' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('dose_info')
                    ->label('Dose')
                    ->getStateUsing(function (Treatment $record): ?string {
                        $parts = [];

                        if ($record->dose_text) {
                            return $record->dose_text;
                        }

                        if ($record->dose_min !== null || $record->dose_max !== null) {
                            $min = $record->dose_min ?? '?';
                            $max = $record->dose_max ?? '?';
                            $unit = $record->dose_unit ?? $record->unit_label ?? '';

                            return "{$min} - {$max} {$unit}";
                        }

                        return null;
                    })
                    ->toggleable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\IconColumn::make('is_deleted')
                    ->label('Deleted')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('treatment_source')
                    ->label('Source')
                    ->options([
                        'market' => 'Market Products',
                        'legacy' => 'Legacy (Drug)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'market' => $query->whereNotNull('market_product_id'),
                            'legacy' => $query->whereNull('market_product_id'),
                            default => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('problem_type')
                    ->label('Problem Type')
                    ->options([
                        'disease' => 'Disease',
                        'pest' => 'Pest',
                        'weed' => 'Weed',
                    ]),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['disease', 'pest', 'drug', 'marketProduct']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTreatments::route('/'),
            'create' => Pages\CreateTreatment::route('/create'),
            'view' => Pages\ViewTreatment::route('/{record}'),
            'edit' => Pages\EditTreatment::route('/{record}/edit'),
        ];
    }
}
