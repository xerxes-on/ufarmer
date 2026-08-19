<?php

declare(strict_types=1);

namespace Modules\Exporter\Filament\Resources;

use App\Filament\NavigationGroup;
use App\Traits\HasTranslatedFilamentLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Core\Models\Region;
use Modules\Crops\Models\Crop;
use Modules\Exporter\Filament\Resources\ExporterRoleResource\Pages;
use Modules\Exporter\Models\ExporterRole;

class ExporterRoleResource extends Resource
{
    use HasTranslatedFilamentLabels;

    protected static ?string $model = ExporterRole::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = NavigationGroup::MarketplaceAndPrices->value;

    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('exporter::filament.resources.role.sections.general'))
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('exporter::filament.resources.role.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label(__('exporter::filament.resources.role.fields.description'))
                        ->rows(3),
                    Forms\Components\Toggle::make('is_unlimited')
                        ->label(__('exporter::filament.resources.role.fields.is_unlimited'))
                        ->helperText(__('exporter::filament.resources.role.fields.is_unlimited_help'))
                        ->reactive()
                        ->default(false),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('exporter::filament.resources.role.fields.is_active'))
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('exporter::filament.resources.role.sections.restrictions'))
                ->schema([
                    Forms\Components\Select::make('region_ids')
                        ->label(__('exporter::filament.resources.role.fields.regions'))
                        ->multiple()
                        ->options(fn () => self::regionOptions())
                        ->placeholder(__('exporter::filament.resources.role.fields.regions_placeholder'))
                        ->helperText(__('exporter::filament.resources.role.fields.regions_help'))
                        ->hidden(fn (Forms\Get $get) => $get('is_unlimited')),
                    Forms\Components\Select::make('crop_ids')
                        ->label(__('exporter::filament.resources.role.fields.crops'))
                        ->multiple()
                        ->options(fn () => self::cropOptions())
                        ->placeholder(__('exporter::filament.resources.role.fields.crops_placeholder'))
                        ->helperText(__('exporter::filament.resources.role.fields.crops_help'))
                        ->hidden(fn (Forms\Get $get) => $get('is_unlimited')),
                ])
                ->columns(2)
                ->hidden(fn (Forms\Get $get) => $get('is_unlimited')),

            Forms\Components\Section::make(__('exporter::filament.resources.role.sections.validity'))
                ->schema([
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label(__('exporter::filament.resources.role.fields.starts_at'))
                        ->helperText(__('exporter::filament.resources.role.fields.starts_at_help')),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label(__('exporter::filament.resources.role.fields.ends_at'))
                        ->helperText(__('exporter::filament.resources.role.fields.ends_at_help')),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('exporter::filament.resources.role.table.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_unlimited')
                    ->label(__('exporter::filament.resources.role.table.unlimited'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region_count')
                    ->label(__('exporter::filament.resources.role.table.regions'))
                    ->getStateUsing(fn (ExporterRole $record) => count($record->region_ids ?? []) ?: 'All'),
                Tables\Columns\TextColumn::make('crop_count')
                    ->label(__('exporter::filament.resources.role.table.crops'))
                    ->getStateUsing(fn (ExporterRole $record) => count($record->crop_ids ?? []) ?: 'All'),
                Tables\Columns\TextColumn::make('validity_status')
                    ->label(__('exporter::filament.resources.role.table.validity'))
                    ->getStateUsing(fn (ExporterRole $record) => self::getValidityStatus($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Not Started' => 'warning',
                        'Expired' => 'danger',
                        'Inactive' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('exporters_count')
                    ->label(__('exporter::filament.resources.role.table.assigned'))
                    ->counts('exporters')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('exporter::filament.resources.role.table.active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('exporter::filament.resources.role.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('exporter::filament.resources.role.filters.active')),
                Tables\Filters\TernaryFilter::make('is_unlimited')
                    ->label(__('exporter::filament.resources.role.filters.unlimited')),
                Tables\Filters\Filter::make('expired')
                    ->label(__('exporter::filament.resources.role.filters.expired'))
                    ->query(fn ($query) => $query->where('ends_at', '<', now())),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExporterRoles::route('/'),
            'create' => Pages\CreateExporterRole::route('/create'),
            'edit' => Pages\EditExporterRole::route('/{record}/edit'),
        ];
    }

    private static function regionOptions(): array
    {
        $locale = app()->getLocale();

        return Region::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Region $region): array => [
                $region->id => $region->getTranslation('name', $locale, false) ?: "Region #{$region->id}",
            ])
            ->toArray();
    }

    private static function cropOptions(): array
    {
        $locale = app()->getLocale();

        return Crop::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Crop $crop): array => [
                $crop->id => $crop->getTranslation('name', $locale, false) ?: "Crop #{$crop->id}",
            ])
            ->toArray();
    }

    private static function getValidityStatus(ExporterRole $record): string
    {
        if (! $record->is_active) {
            return 'Inactive';
        }

        if ($record->isNotStarted()) {
            return 'Not Started';
        }

        if ($record->isExpired()) {
            return 'Expired';
        }

        return 'Active';
    }
}
