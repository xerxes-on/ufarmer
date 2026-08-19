<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use JsonException;
use Modules\AgroCalculator\Filament\Resources\RecommendationRuleResource\Pages;
use Modules\AgroCalculator\Models\RecommendationRule;

class RecommendationRuleResource extends Resource
{
    protected static ?string $model = RecommendationRule::class;

    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('agrocalculator::filament.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('agrocalculator::filament.resources.recommendation_rule.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('agrocalculator::filament.resources.recommendation_rule.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('agrocalculator::filament.resources.recommendation_rule.plural_label');
    }

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)->schema([
                TextInput::make('code')
                    ->label(__('agrocalculator::filament.resources.recommendation_rule.form.code'))
                    ->required()
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->label(__('agrocalculator::filament.resources.recommendation_rule.form.is_active'))
                    ->default(true)
                    ->required(),
            ]),
            Textarea::make('title')
                ->label(__('agrocalculator::filament.resources.recommendation_rule.form.title'))
                ->rows(6)
                ->required()
                ->afterStateHydrated(function (Textarea $component, $state): void {
                    $component->state(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                })
                ->dehydrateStateUsing(function ($state) {
                    if ($state === null || $state === '') {
                        return [];
                    }

                    try {
                        return json_decode((string) $state, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        throw ValidationException::withMessages([
                            'title' => __('validation.json'),
                        ]);
                    }
                })
                ->rule('json'),
            Textarea::make('conditions')
                ->label(__('agrocalculator::filament.resources.recommendation_rule.form.conditions'))
                ->rows(10)
                ->required()
                ->afterStateHydrated(function (Textarea $component, $state): void {
                    $component->state(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                })
                ->dehydrateStateUsing(function ($state) {
                    if ($state === null || $state === '') {
                        return [];
                    }

                    try {
                        return json_decode((string) $state, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        throw ValidationException::withMessages([
                            'conditions' => __('validation.json'),
                        ]);
                    }
                })
                ->rule('json'),
            Textarea::make('recommendations')
                ->label(__('agrocalculator::filament.resources.recommendation_rule.form.recommendations'))
                ->rows(10)
                ->required()
                ->afterStateHydrated(function (Textarea $component, $state): void {
                    $component->state(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                })
                ->dehydrateStateUsing(function ($state) {
                    if ($state === null || $state === '') {
                        return [];
                    }

                    try {
                        return json_decode((string) $state, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException) {
                        throw ValidationException::withMessages([
                            'recommendations' => __('validation.json'),
                        ]);
                    }
                })
                ->rule('json'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('agrocalculator::filament.resources.recommendation_rule.table.columns.code'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title.en')
                    ->label(__('agrocalculator::filament.resources.recommendation_rule.table.columns.title_en'))
                    ->wrap(),
                BadgeColumn::make('is_active')
                    ->label(__('agrocalculator::filament.resources.recommendation_rule.table.columns.is_active'))
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                TextColumn::make('updated_at')
                    ->label(__('agrocalculator::filament.resources.recommendation_rule.table.columns.updated'))
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecommendationRules::route('/'),
            'create' => Pages\CreateRecommendationRule::route('/create'),
            'edit' => Pages\EditRecommendationRule::route('/{record}/edit'),
        ];
    }
}
