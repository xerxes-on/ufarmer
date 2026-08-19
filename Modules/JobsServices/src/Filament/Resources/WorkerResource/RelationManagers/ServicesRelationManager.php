<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\WorkerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Core\Enums\Currency;
use Modules\Core\Enums\PriceUnit;
use Modules\JobsServices\Models\ServiceCategory;
use Modules\JobsServices\Models\ServiceOffer;
use Modules\JobsServices\Services\AiImport\Worker\WorkerRowValidator;

/**
 * Services offered by a worker.
 *
 * `service_offers.user_id` references the user account, so new rows are
 * stamped with the owning profile's `user_id` rather than the relation's
 * default parent key.
 */
class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.relation_labels.services');
    }

    protected static ?string $icon = 'heroicon-o-briefcase';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin-panel.resources.worker.sections.service'))
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label(__('admin-panel.resources.worker.fields.category'))
                            ->options(fn (): array => ServiceCategory::query()
                                ->active()
                                ->whereIn('applies_to', ['offer', 'both'])
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn (ServiceCategory $category): array => [
                                    $category->id => $category->getTranslation('name', app()->getLocale())
                                        ?: $category->getTranslation('name', 'uz'),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title.uz')
                            ->label(__('admin-panel.resources.worker.fields.title_uz'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title.ru')
                            ->label(__('admin-panel.resources.worker.fields.title_ru'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('title.en')
                            ->label(__('admin-panel.resources.worker.fields.title_en'))
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description.uz')
                            ->label(__('admin-panel.resources.worker.fields.description_uz'))
                            ->rows(3)
                            ->maxLength(5000),
                        Forms\Components\Textarea::make('description.ru')
                            ->label(__('admin-panel.resources.worker.fields.description_ru'))
                            ->rows(3)
                            ->maxLength(5000),
                        Forms\Components\Textarea::make('description.en')
                            ->label(__('admin-panel.resources.worker.fields.description_en'))
                            ->rows(3)
                            ->maxLength(5000),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('admin-panel.resources.worker.sections.pricing'))
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label(__('admin-panel.resources.worker.fields.price'))
                            ->numeric()
                            ->minValue(0)
                            // Canonical cap — must match ufarm-api's config('services.pricing.max_price')
                            // and ufarm-billing's payment_systems max_amount (UFARM-2767).
                            ->maxValue(WorkerRowValidator::MAX_PRICE)
                            ->required(),
                        Forms\Components\Select::make('price_unit')
                            ->label(__('admin-panel.resources.worker.fields.price_unit'))
                            ->options(PriceUnit::options())
                            ->default(PriceUnit::PerHour->value)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('currency')
                            ->label(__('admin-panel.resources.worker.fields.currency'))
                            ->options(Currency::options())
                            ->default(Currency::UZS->value)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('admin-panel.resources.worker.sections.location'))
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label(__('admin-panel.resources.worker.fields.latitude'))
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->step(0.0000001)
                            ->required(),
                        Forms\Components\TextInput::make('longitude')
                            ->label(__('admin-panel.resources.worker.fields.longitude'))
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->step(0.0000001)
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->label(__('admin-panel.resources.worker.fields.address'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        // `service_offers` stores region/city as free text, not
                        // as FKs — kept as-is so the mobile app's existing
                        // reads keep working.
                        Forms\Components\TextInput::make('region')
                            ->label(__('admin-panel.resources.worker.fields.region'))
                            ->maxLength(120),
                        Forms\Components\TextInput::make('city')
                            ->label(__('admin-panel.resources.worker.fields.city'))
                            ->maxLength(120),
                        Forms\Components\TextInput::make('service_radius_km')
                            ->label(__('admin-panel.resources.worker.fields.service_radius_km'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(9999),
                    ])
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin-panel.resources.worker.fields.title'))
                    ->getStateUsing(fn (ServiceOffer $record) => self::localized($record->getAttributes()['title'] ?? null))
                    ->wrap()
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('title->uz', 'ilike', "%{$search}%")
                        ->orWhere('title->ru', 'ilike', "%{$search}%")
                        ->orWhere('title->en', 'ilike', "%{$search}%")),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('admin-panel.resources.worker.fields.category'))
                    ->getStateUsing(fn (ServiceOffer $record) => $record->category
                        ? ($record->category->getTranslation('name', app()->getLocale())
                            ?: $record->category->getTranslation('name', 'uz'))
                        : '-')
                    ->badge(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('admin-panel.resources.worker.fields.price'))
                    ->money(fn (ServiceOffer $record): string => $record->currency ?? Currency::UZS->value)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_unit')
                    ->label(__('admin-panel.resources.worker.fields.price_unit'))
                    ->getStateUsing(fn (ServiceOffer $record) => PriceUnit::tryFrom((string) $record->price_unit)?->translatedLabel() ?? $record->price_unit)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin-panel.resources.worker.fields.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('views_count')
                    ->label(__('admin-panel.resources.worker.fields.views'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin-panel.resources.worker.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    // service_offers.user_id points at the account, and the
                    // relation's parent here is the profile row.
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = $this->getOwnerRecord()->user_id;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * service_offers.title/description are plain JSON columns (no
     * spatie/translatable on this model), so the locale is resolved by hand.
     */
    private static function localized(mixed $json): string
    {
        $values = is_array($json) ? $json : json_decode((string) $json, true);

        if (! is_array($values)) {
            return '-';
        }

        foreach ([app()->getLocale(), 'uz', 'ru', 'en'] as $locale) {
            $value = $values[$locale] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '-';
    }
}
