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
use Modules\Core\Enums\PaidServiceStatus;
use Modules\Core\Enums\UserRole;
use Modules\Core\Filament\Resources\PaidServiceResource\Pages;
use Modules\Core\Models\PaidService;

class PaidServiceResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = PaidService::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = NavigationGroup::Administration->value;

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->helperText('Unique identifier for the service'),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(PaidServiceStatus::options())
                        ->required()
                        ->default(PaidServiceStatus::Active->value),
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
                    Forms\Components\Toggle::make('is_paid')
                        ->label('Is Paid Service')
                        ->default(false)
                        ->live(),
                    Forms\Components\TextInput::make('price')
                        ->label('Price')
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn (Forms\Get $get): bool => $get('is_paid'))
                        ->required(fn (Forms\Get $get): bool => $get('is_paid')),
                    Forms\Components\TextInput::make('currency')
                        ->label('Currency')
                        ->default('UZS')
                        ->maxLength(10)
                        ->visible(fn (Forms\Get $get): bool => $get('is_paid')),
                ])
                ->columns(3),

            Forms\Components\Section::make('Access Control')
                ->schema([
                    Forms\Components\CheckboxList::make('applicable_roles')
                        ->label('Applicable Roles')
                        ->options(UserRole::options())
                        ->helperText('Leave empty to apply to all roles')
                        ->columns(2),
                ]),

            Forms\Components\Section::make('Validity Period')
                ->schema([
                    Forms\Components\DateTimePicker::make('valid_from')
                        ->label('Valid From'),
                    Forms\Components\DateTimePicker::make('valid_until')
                        ->label('Valid Until')
                        ->after('valid_from'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Configuration')
                ->schema([
                    Forms\Components\TextInput::make('config.free_trials')
                        ->label('Free Trials Allowed')
                        ->numeric()
                        ->minValue(0)
                        ->default(1)
                        ->helperText('Number of free trials allowed per user (for analysis service)')
                        ->visible(fn (Forms\Get $get): bool => $get('code') === 'analysis'),
                    Forms\Components\KeyValue::make('config')
                        ->label('Additional Configuration')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->addActionLabel('Add Config')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('localized_name')
                    ->label('Name')
                    ->searchable(['name'])
                    ->limit(30),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money(fn (PaidService $record): string => $record->currency ?? 'UZS')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('applicable_roles')
                    ->label('Roles')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? implode(', ', $state) : 'All')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PaidServiceStatus $state): string => match ($state) {
                        PaidServiceStatus::Active => 'success',
                        PaidServiceStatus::Inactive => 'warning',
                        PaidServiceStatus::Deprecated => 'danger',
                    })
                    ->formatStateUsing(fn (PaidServiceStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Valid From')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(PaidServiceStatus::options()),
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payment Type')
                    ->placeholder('All')
                    ->trueLabel('Paid Only')
                    ->falseLabel('Free Only'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleStatus')
                    ->label('Toggle Status')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (PaidService $record): void {
                        $newStatus = $record->status === PaidServiceStatus::Active
                            ? PaidServiceStatus::Inactive
                            : PaidServiceStatus::Active;
                        $record->update(['status' => $newStatus]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListPaidServices::route('/'),
            'create' => Pages\CreatePaidService::route('/create'),
            'edit' => Pages\EditPaidService::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
