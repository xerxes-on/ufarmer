<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Core\Filament\Resources\StatOptionResource\Pages;
use Modules\Core\Models\StatOption;

class StatOptionResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = StatOption::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Onboarding Options';

    protected static ?string $navigationGroup = NavigationGroup::Administration->value;

    protected static ?int $navigationSort = 70;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Icon & Color')
                    ->schema([
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon URL')
                            ->required()
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://api.iconify.design/mdi:account.svg?color=%239333ea')
                            ->helperText('Iconify URL with color parameter'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Title (Translatable)')
                    ->schema([
                        Forms\Components\TextInput::make('title.uz')
                            ->label('Title (Uzbek)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title.en')
                            ->label('Title (English)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title.ru')
                            ->label('Title (Russian)')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Body (Translatable)')
                    ->schema([
                        Forms\Components\Textarea::make('body.uz')
                            ->label('Body (Uzbek)')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Textarea::make('body.en')
                            ->label('Body (English)')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Textarea::make('body.ru')
                            ->label('Body (Russian)')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('Icon')
                    ->circular()
                    ->size(40),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Color'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('body')
                    ->label('Body')
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListStatOptions::route('/'),
            'create' => Pages\CreateStatOption::route('/create'),
            'edit' => Pages\EditStatOption::route('/{record}/edit'),
        ];
    }
}
