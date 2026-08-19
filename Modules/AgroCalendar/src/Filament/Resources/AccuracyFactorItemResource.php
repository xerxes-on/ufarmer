<?php

declare(strict_types=1);

namespace Modules\AgroCalendar\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\AgroCalendar\Filament\Resources\AccuracyFactorItemResource\Pages;
use Modules\AgroCalendar\Models\AccuracyFactor;
use Modules\AgroCalendar\Models\AccuracyFactorItem;

class AccuracyFactorItemResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = AccuracyFactorItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Accuracy Factor Items';

    protected static ?string $navigationGroup = NavigationGroup::AgroCalculator->value;

    protected static ?int $navigationSort = 50;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('accuracy_factor_id')
                    ->label('Accuracy Factor')
                    ->relationship('factor', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (AccuracyFactor $factor): string => $factor->getTranslation('name', app()->getLocale()) ?: $factor->getTranslation('name', config('app.fallback_locale', 'en')))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name.uz')
                    ->label('Name (Uzbek)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name.ru')
                    ->label('Name (Russian)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name.en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description.uz')
                    ->label('Description (Uzbek)')
                    ->rows(2),
                Forms\Components\Textarea::make('description.ru')
                    ->label('Description (Russian)')
                    ->rows(2),
                Forms\Components\Textarea::make('description.en')
                    ->label('Description (English)')
                    ->rows(2),
                Forms\Components\TextInput::make('order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('link')
                    ->url()
                    ->maxLength(2048)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('factor.name')
                    ->label('Accuracy Factor')
                    ->formatStateUsing(fn (AccuracyFactorItem $record): ?string => $record->factor?->getTranslation('name', app()->getLocale()) ?: $record->factor?->getTranslation('name', config('app.fallback_locale', 'en'))),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (AccuracyFactorItem $record): string => $record->getTranslation('name', app()->getLocale()) ?: $record->getTranslation('name', config('app.fallback_locale', 'en')))
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('link')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('accuracy_factor_id')
                    ->label('Accuracy Factor')
                    ->relationship('factor', 'slug')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccuracyFactorItems::route('/'),
            'create' => Pages\CreateAccuracyFactorItem::route('/create'),
            'edit' => Pages\EditAccuracyFactorItem::route('/{record}/edit'),
        ];
    }
}
