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
use Modules\Crops\Filament\Resources\RecommendationResource\Pages;
use Modules\Crops\Models\Recommendation;

class RecommendationResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = Recommendation::class;

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Agro Recommendations';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('parameter_key')
                ->label('Parameter Key')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Unique key for this parameter (e.g., humus, ph, nitrogen)')
                ->columnSpanFull(),

            Forms\Components\Section::make('Parameter Name')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('parameter_name.en')
                            ->label('English')
                            ->required(),
                        Forms\Components\TextInput::make('parameter_name.ru')
                            ->label('Russian')
                            ->required(),
                        Forms\Components\TextInput::make('parameter_name.uz')
                            ->label('Uzbek')
                            ->required(),
                    ]),
                ]),

            Forms\Components\Section::make('Recommendation')
                ->schema([
                    Forms\Components\Grid::make(1)->schema([
                        Forms\Components\TextInput::make('recommendation.en')
                            ->label('English Recommendation')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('recommendation.ru')
                            ->label('Russian Recommendation')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('recommendation.uz')
                            ->label('Uzbek Recommendation')
                            ->required()
                            ->maxLength(255),
                    ]),
                ]),

            Forms\Components\Section::make('Justification')
                ->schema([
                    Forms\Components\Grid::make(1)->schema([
                        Forms\Components\Textarea::make('justification.en')
                            ->label('English Justification')
                            ->required()
                            ->rows(2),
                        Forms\Components\Textarea::make('justification.ru')
                            ->label('Russian Justification')
                            ->required()
                            ->rows(2),
                        Forms\Components\Textarea::make('justification.uz')
                            ->label('Uzbek Justification')
                            ->required()
                            ->rows(2),
                    ]),
                ]),

            Forms\Components\Section::make('Module & Timing')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('module.en')
                                ->label('Module (English)')
                                ->required(),
                            Forms\Components\TextInput::make('module.ru')
                                ->label('Module (Russian)')
                                ->required(),
                            Forms\Components\TextInput::make('module.uz')
                                ->label('Module (Uzbek)')
                                ->required(),
                        ])->columnSpan(1),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('timing.en')
                                ->label('Timing (English)')
                                ->required(),
                            Forms\Components\TextInput::make('timing.ru')
                                ->label('Timing (Russian)')
                                ->required(),
                            Forms\Components\TextInput::make('timing.uz')
                                ->label('Timing (Uzbek)')
                                ->required(),
                        ])->columnSpan(1),
                    ]),
                ]),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('parameter_name')
                    ->label('Parameter')
                    ->getStateUsing(fn ($record) => $record->parameter_name[$locale] ?? $record->parameter_name['en'] ?? '')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recommendation')
                    ->label('Recommendation')
                    ->getStateUsing(fn ($record) => $record->recommendation[$locale] ?? $record->recommendation['en'] ?? '')
                    ->limit(50),
                Tables\Columns\TextColumn::make('module')
                    ->label('Module')
                    ->getStateUsing(fn ($record) => $record->module[$locale] ?? $record->module['en'] ?? ''),
                Tables\Columns\TextColumn::make('timing')
                    ->label('Timing')
                    ->getStateUsing(fn ($record) => $record->timing[$locale] ?? $record->timing['en'] ?? ''),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecommendations::route('/'),
            'create' => Pages\CreateRecommendation::route('/create'),
            'edit' => Pages\EditRecommendation::route('/{record}/edit'),
        ];
    }
}
