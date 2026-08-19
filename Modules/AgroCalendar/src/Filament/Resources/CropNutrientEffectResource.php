<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\AgroCalendar\Enums\NutrientEffectType;
use Modules\AgroCalendar\Filament\Resources\CropNutrientEffectResource\Pages;
use Modules\AgroCalendar\Models\CropNutrientEffect;

class CropNutrientEffectResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = CropNutrientEffect::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Crop Nutrient Effects';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Crop & Nutrient')
                    ->schema([
                        Forms\Components\Select::make('crop_id')
                            ->label('Crop')
                            ->relationship('crop', 'name->en')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('nutrient_code')
                            ->label('Nutrient Code')
                            ->required()
                            ->maxLength(32)
                            ->placeholder('e.g., nitrogen, phosphorus, potassium'),
                        Forms\Components\Select::make('effect_type')
                            ->label('Effect Type')
                            ->options(NutrientEffectType::options())
                            ->required(),
                        Forms\Components\TextInput::make('effect_percentage')
                            ->label('Effect Percentage')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->required()
                            ->helperText('Negative for depletion, positive for addition'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description.uz')
                            ->label('Description (Uzbek)')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description.ru')
                            ->label('Description (Russian)')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description.en')
                            ->label('Description (English)')
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Metadata')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('crop.name')
                    ->label('Crop')
                    ->getStateUsing(fn (CropNutrientEffect $record): string => self::localized($record->crop?->getTranslations('name')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('crop', function (Builder $q) use ($search) {
                            $q->where('name->uz', 'like', "%{$search}%")
                                ->orWhere('name->en', 'like', "%{$search}%")
                                ->orWhere('name->ru', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('nutrient_code')
                    ->label('Nutrient')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nitrogen' => 'success',
                        'phosphorus' => 'warning',
                        'potassium' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('effect_type')
                    ->label('Effect')
                    ->badge()
                    ->formatStateUsing(fn (CropNutrientEffect $record): string => self::localized($record->effect_type?->label() ?? []))
                    ->color(fn (CropNutrientEffect $record): string => $record->effect_type === NutrientEffectType::ADD ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('effect_percentage')
                    ->label('Percentage')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn (CropNutrientEffect $record): string => $record->effect_percentage > 0 ? 'success' : 'danger'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Filters\SelectFilter::make('effect_type')
                    ->label('Effect Type')
                    ->options(NutrientEffectType::options()),
                Tables\Filters\SelectFilter::make('nutrient_code')
                    ->label('Nutrient')
                    ->options([
                        'nitrogen' => 'Nitrogen (N)',
                        'phosphorus' => 'Phosphorus (P)',
                        'potassium' => 'Potassium (K)',
                    ]),
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
            ->defaultSort('crop_id');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropNutrientEffects::route('/'),
            'create' => Pages\CreateCropNutrientEffect::route('/create'),
            'edit' => Pages\EditCropNutrientEffect::route('/{record}/edit'),
        ];
    }

    private static function localized(array|string|null $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '-';
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($value[$locale] ?? $value[$fallback] ?? $value['uz'] ?? reset($value) ?: '-');
    }
}
