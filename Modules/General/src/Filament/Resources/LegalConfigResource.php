<?php

declare(strict_types=1);

namespace Modules\General\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\General\Filament\Resources\LegalConfigResource\Pages;
use Modules\General\Models\LegalConfig;

class LegalConfigResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = LegalConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Legal Settings';

    protected static ?string $navigationGroup = NavigationGroup::Content->value;

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Legal Setting';

    protected static ?string $pluralModelLabel = 'Legal Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('value_type')
                            ->label('Value Type')
                            ->required()
                            ->options([
                                LegalConfig::TYPE_STRING => 'String',
                                LegalConfig::TYPE_INTEGER => 'Integer',
                                LegalConfig::TYPE_FLOAT => 'Float',
                                LegalConfig::TYPE_BOOLEAN => 'Boolean',
                                LegalConfig::TYPE_JSON => 'JSON',
                                LegalConfig::TYPE_ENUM => 'Enum',
                            ])
                            ->default(LegalConfig::TYPE_STRING)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('value', null)),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Public')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Value')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->required()
                            ->maxLength(65535)
                            ->visible(fn (Get $get): bool => in_array((string) $get('value_type'), [
                                LegalConfig::TYPE_STRING,
                                LegalConfig::TYPE_INTEGER,
                                LegalConfig::TYPE_FLOAT,
                                LegalConfig::TYPE_ENUM,
                            ], true)),

                        Forms\Components\Toggle::make('value')
                            ->label('Value')
                            ->visible(fn (Get $get): bool => $get('value_type') === LegalConfig::TYPE_BOOLEAN)
                            ->dehydrateStateUsing(fn ($state) => $state ? 'true' : 'false')
                            ->afterStateHydrated(fn ($component, $state) => $component->state(filter_var($state, FILTER_VALIDATE_BOOLEAN))),

                        Forms\Components\Textarea::make('value')
                            ->label('Value (JSON)')
                            ->required()
                            ->rows(8)
                            ->rules(['json'])
                            ->visible(fn (Get $get): bool => $get('value_type') === LegalConfig::TYPE_JSON),
                    ]),

                Forms\Components\Section::make('Description (Translatable)')
                    ->schema([
                        Forms\Components\Textarea::make('description.uz')
                            ->label('Description (Uzbek)')
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\Textarea::make('description.en')
                            ->label('Description (English)')
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\Textarea::make('description.ru')
                            ->label('Description (Russian)')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('value_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        LegalConfig::TYPE_STRING => 'primary',
                        LegalConfig::TYPE_INTEGER => 'success',
                        LegalConfig::TYPE_FLOAT => 'warning',
                        LegalConfig::TYPE_BOOLEAN => 'info',
                        LegalConfig::TYPE_JSON => 'danger',
                        LegalConfig::TYPE_ENUM => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->limit(60)
                    ->tooltip(fn (LegalConfig $record): ?string => $record->value),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('key')
            ->filters([
                Tables\Filters\SelectFilter::make('value_type')
                    ->options([
                        LegalConfig::TYPE_STRING => 'String',
                        LegalConfig::TYPE_INTEGER => 'Integer',
                        LegalConfig::TYPE_FLOAT => 'Float',
                        LegalConfig::TYPE_BOOLEAN => 'Boolean',
                        LegalConfig::TYPE_JSON => 'JSON',
                        LegalConfig::TYPE_ENUM => 'Enum',
                    ]),
                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public'),
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
            'index' => Pages\ListLegalConfigs::route('/'),
            'create' => Pages\CreateLegalConfig::route('/create'),
            'edit' => Pages\EditLegalConfig::route('/{record}/edit'),
        ];
    }
}
