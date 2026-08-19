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
use Modules\PlantScanner\Filament\Resources\PestDetailResource\Pages;
use Modules\PlantScanner\Models\PestDetail;

class PestDetailResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = PestDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Pest Details';

    protected static ?string $navigationGroup = NavigationGroup::Diagnostics->value;

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')
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

                Forms\Components\Section::make('Affected Crops')
                    ->schema([
                        Forms\Components\Textarea::make('affected_crops_uz')
                            ->label('Affected Crops (Uzbek)')
                            ->rows(2),
                        Forms\Components\Textarea::make('affected_crops_ru')
                            ->label('Affected Crops (Russian)')
                            ->rows(2),
                        Forms\Components\Textarea::make('affected_crops_en')
                            ->label('Affected Crops (English)')
                            ->rows(2),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Damage Signs')
                    ->schema([
                        Forms\Components\Textarea::make('damage_signs_uz')
                            ->label('Damage Signs (Uzbek)')
                            ->rows(2),
                        Forms\Components\Textarea::make('damage_signs_ru')
                            ->label('Damage Signs (Russian)')
                            ->rows(2),
                        Forms\Components\Textarea::make('damage_signs_en')
                            ->label('Damage Signs (English)')
                            ->rows(2),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Prevention')
                    ->schema([
                        Forms\Components\Textarea::make('prevention_uz')
                            ->label('Prevention (Uzbek)')
                            ->rows(2),
                        Forms\Components\Textarea::make('prevention_ru')
                            ->label('Prevention (Russian)')
                            ->rows(2),
                        Forms\Components\Textarea::make('prevention_en')
                            ->label('Prevention (English)')
                            ->rows(2),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Control Methods')
                    ->schema([
                        Forms\Components\Textarea::make('organic_control_uz')
                            ->label('Organic Control (Uzbek)')
                            ->rows(2),
                        Forms\Components\Textarea::make('organic_control_ru')
                            ->label('Organic Control (Russian)')
                            ->rows(2),
                        Forms\Components\Textarea::make('organic_control_en')
                            ->label('Organic Control (English)')
                            ->rows(2),
                        Forms\Components\Textarea::make('chemical_control_uz')
                            ->label('Chemical Control (Uzbek)')
                            ->rows(2),
                        Forms\Components\Textarea::make('chemical_control_ru')
                            ->label('Chemical Control (Russian)')
                            ->rows(2),
                        Forms\Components\Textarea::make('chemical_control_en')
                            ->label('Chemical Control (English)')
                            ->rows(2),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state?->color())
                    ->sortable(),
                Tables\Columns\TextColumn::make('scientific_name')
                    ->label('Scientific Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('common_name_uz')
                    ->label('Common Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('affected_crops_uz')
                    ->label('Affected Crops')
                    ->limit(50)
                    ->toggleable(),
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
            'index' => Pages\ListPestDetails::route('/'),
            'create' => Pages\CreatePestDetail::route('/create'),
            'edit' => Pages\EditPestDetail::route('/{record}/edit'),
        ];
    }
}
