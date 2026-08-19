<?php

declare(strict_types=1);

namespace Modules\Analysis\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Analysis\Enums\AnalysisTypeSlug;
use Modules\Analysis\Filament\Resources\AnalysisTypeResource\Pages;
use Modules\Analysis\Models\AnalysisType;

class AnalysisTypeResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AnalysisType::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = NavigationGroup::Diagnostics->value;

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\Select::make('slug')
                        ->label('Type')
                        ->options(AnalysisTypeSlug::options())
                        ->required()
                        ->disabled(fn (?AnalysisType $record): bool => $record !== null),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\Toggle::make('requires_crops')
                        ->label('Requires Crops')
                        ->helperText('If enabled, user must select crops for this analysis'),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),

            Forms\Components\Tabs::make('Translations')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('English')
                        ->schema([
                            Forms\Components\TextInput::make('name.en')
                                ->label('Name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description.en')
                                ->label('Description')
                                ->rows(3),
                        ]),
                    Forms\Components\Tabs\Tab::make('Uzbek')
                        ->schema([
                            Forms\Components\TextInput::make('name.uz')
                                ->label('Name')
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description.uz')
                                ->label('Description')
                                ->rows(3),
                        ]),
                    Forms\Components\Tabs\Tab::make('Russian')
                        ->schema([
                            Forms\Components\TextInput::make('name.ru')
                                ->label('Name')
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description.ru')
                                ->label('Description')
                                ->rows(3),
                        ]),
                ])
                ->columnSpanFull(),

            Forms\Components\Section::make('Pricing')
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Price (in tiyin)')
                        ->numeric()
                        ->default(0)
                        ->rules(['nullable', 'integer', function (string $attribute, $value, $fail) {
                            if ($value > 0 && $value < 100) {
                                $fail('Minimum paid price is 100 tiyin (billing requirement).');
                            }
                        }])
                        ->helperText('Price in tiyin. Set 0 for free, or minimum 100 for paid (billing requirement).'),
                    Forms\Components\TextInput::make('currency')
                        ->label('Currency')
                        ->default('UZS')
                        ->maxLength(10),
                ])
                ->columns(2),

            Forms\Components\Section::make('Display')
                ->schema([
                    Forms\Components\TextInput::make('icon_url')
                        ->label('Icon URL')
                        ->url()
                        ->maxLength(500)
                        ->helperText('URL to the icon image for this analysis type'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AnalysisTypeSlug::tryFrom($state)?->label() ?? str($state)->headline()->toString())
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(function (int $state): string {
                        if ($state === 0) {
                            return 'Free';
                        }

                        return number_format($state / 100, 0, '.', ' ').' UZS';
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('requires_crops')
                    ->label('Requires Crops')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnalysisTypes::route('/'),
            'create' => Pages\CreateAnalysisType::route('/create'),
            'edit' => Pages\EditAnalysisType::route('/{record}/edit'),
        ];
    }
}
