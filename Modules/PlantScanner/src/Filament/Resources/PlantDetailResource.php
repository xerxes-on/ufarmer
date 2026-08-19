<?php

declare(strict_types=1);

namespace Modules\PlantScanner\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PlantScanner\Filament\Resources\PlantDetailResource\Pages;
use Modules\PlantScanner\Models\PlantDetail;

class PlantDetailResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = PlantDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Plant Details';

    protected static ?string $navigationGroup = NavigationGroup::Diagnostics->value;

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Names')
                    ->schema([
                        Forms\Components\TextInput::make('scientific_name')
                            ->label('Scientific Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('common_name_uz')
                            ->label('Common Name (Uzbek)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('common_name_ru')
                            ->label('Common Name (Russian)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('common_name_en')
                            ->label('Common Name (English)')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Family')
                    ->schema([
                        Forms\Components\TextInput::make('family_uz')
                            ->label('Family (Uzbek)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('family_ru')
                            ->label('Family (Russian)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('family_en')
                            ->label('Family (English)')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description_uz')
                            ->label('Description (Uzbek)')
                            ->rows(3),
                        Forms\Components\Textarea::make('description_ru')
                            ->label('Description (Russian)')
                            ->rows(3),
                        Forms\Components\Textarea::make('description_en')
                            ->label('Description (English)')
                            ->rows(3),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Growing Conditions')
                    ->schema([
                        Forms\Components\TextInput::make('temperature_min')
                            ->label('Min Temperature (°C)')
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\TextInput::make('temperature_max')
                            ->label('Max Temperature (°C)')
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\TextInput::make('soil_ph_min')
                            ->label('Min Soil pH')
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\TextInput::make('soil_ph_max')
                            ->label('Max Soil pH')
                            ->numeric()
                            ->step(0.1),
                        Forms\Components\TextInput::make('humidity_min')
                            ->label('Min Humidity (%)')
                            ->numeric(),
                        Forms\Components\TextInput::make('humidity_max')
                            ->label('Max Humidity (%)')
                            ->numeric(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Tags')
                    ->schema([
                        Forms\Components\Select::make('tags')
                            ->relationship('tags', 'name_uz')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scientific_name')
                    ->label('Scientific Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('common_name_uz')
                    ->label('Common Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('family_uz')
                    ->label('Family')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('temperature_min')
                    ->label('Temp Range')
                    ->formatStateUsing(fn ($record) => $record->temperature_min && $record->temperature_max
                        ? "{$record->temperature_min}° - {$record->temperature_max}°"
                        : '-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('scanned_plants_count')
                    ->label('Scans')
                    ->counts('scannedPlants')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlantDetails::route('/'),
            'create' => Pages\CreatePlantDetail::route('/create'),
            'edit' => Pages\EditPlantDetail::route('/{record}/edit'),
        ];
    }
}
