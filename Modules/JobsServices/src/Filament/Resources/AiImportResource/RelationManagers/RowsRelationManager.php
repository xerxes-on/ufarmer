<?php

declare(strict_types=1);

namespace Modules\JobsServices\Filament\Resources\AiImportResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\Currency;
use Modules\Core\Enums\PriceUnit;
use Modules\Core\Models\City;
use Modules\Core\Models\Region;
use Modules\JobsServices\Enums\AiImportRowStatus;
use Modules\JobsServices\Models\AiImportAlias;
use Modules\JobsServices\Models\AiImportRow;
use Modules\JobsServices\Models\ServiceCategory;
use Modules\JobsServices\Services\AiImport\Worker\WorkerRowValidator;
use Modules\JobsServices\Support\OfferLocales;

/**
 * The rows an import staged, and where an admin corrects them (UFARM-2644).
 *
 * Every editable value lives inside the row's `data` JSON, so the form reads
 * and writes through `data.*` paths rather than columns — that indirection is
 * what lets one review screen serve any future importer.
 *
 * Corrections are also teaching: when an admin picks a category or region by
 * hand, the choice is written to the alias table, so the next import of the
 * same source resolves it without asking the model. That single behaviour is
 * what makes the second import of a recurring sheet nearly free.
 */
class RowsRelationManager extends RelationManager
{
    protected static string $relationship = 'rows';

    protected static ?string $icon = 'heroicon-o-table-cells';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin-panel.resources.ai_import.sections.rows');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('admin-panel.resources.ai_import.fields.person_name'))
                ->schema([
                    Forms\Components\TextInput::make('data.person_name')
                        ->label(__('admin-panel.resources.ai_import.fields.person_name'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('data.company')
                        ->label(__('admin-panel.resources.ai_import.fields.company'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('data.phone')
                        ->label(__('admin-panel.resources.ai_import.fields.phone'))
                        ->tel()
                        ->maxLength(20)
                        ->helperText('998XXXXXXXXX'),
                    Forms\Components\Select::make('data.language')
                        ->label(__('admin-panel.resources.ai_import.fields.language'))
                        ->options(fn (): array => collect(OfferLocales::all())
                            ->mapWithKeys(fn (string $locale): array => [$locale => OfferLocales::label($locale)])
                            ->all())
                        ->native(false),
                    Forms\Components\Textarea::make('data.bio')
                        ->label(__('admin-panel.resources.ai_import.fields.bio'))
                        ->rows(2)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('data.experience_years')
                        ->label(__('admin-panel.resources.ai_import.fields.experience_years'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(99),
                    Forms\Components\TagsInput::make('data.specializations')
                        ->label(__('admin-panel.resources.ai_import.fields.specializations'))
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make(__('admin-panel.resources.ai_import.fields.title'))
                ->schema([
                    Forms\Components\Select::make('data.category_id')
                        ->label(__('admin-panel.resources.ai_import.fields.category'))
                        // Inactive categories are included, and marked. An
                        // import can create one for review (UFARM-2671), and
                        // filtering to active only would hide the very category
                        // the row in front of you is already using — leaving no
                        // way to keep it, only to replace it.
                        ->options(fn (): array => ServiceCategory::query()
                            ->whereIn('applies_to', ['offer', 'both'])
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn (ServiceCategory $category): array => [
                                $category->id => $category->is_active
                                    ? $category->localized_name
                                    : $category->localized_name.' — '.__('admin-panel.resources.ai_import.fields.category_inactive'),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        // The commonest reason a row cannot publish is that the
                        // sheet names a service the catalog has never heard of,
                        // and every one of those rows is stuck until somebody
                        // adds it. Creating it here beats losing the review.
                        // Null disables the option, which is how the permission
                        // this bypasses is honoured.
                        ->createOptionForm(auth()->user()?->can('create', ServiceCategory::class)
                            ? self::categoryFields()
                            : null)
                        ->createOptionUsing(fn (array $data): int => self::createCategory($data))
                        ->columnSpanFull(),
                    ...self::localizedOfferFields('title', required: true),
                    ...self::localizedOfferFields('description'),
                    Forms\Components\TextInput::make('data.price')
                        ->label(__('admin-panel.resources.ai_import.fields.price'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(WorkerRowValidator::MAX_PRICE),
                    Forms\Components\Select::make('data.price_unit')
                        ->label(__('admin-panel.resources.ai_import.fields.price_unit'))
                        ->options(PriceUnit::options())
                        ->native(false),
                    Forms\Components\Select::make('data.currency')
                        ->label(__('admin-panel.resources.ai_import.fields.currency'))
                        ->options(Currency::options())
                        ->native(false),
                ])
                ->columns(3),

            Forms\Components\Section::make(__('admin-panel.resources.ai_import.fields.region'))
                ->schema([
                    Forms\Components\Select::make('data.region_id')
                        ->label(__('admin-panel.resources.ai_import.fields.region'))
                        ->options(fn (): array => Region::query()
                            ->get()
                            ->mapWithKeys(fn (Region $region): array => [$region->id => $region->localized_name])
                            ->all())
                        ->searchable()
                        ->live()
                        // A city left over from the previous region is no
                        // longer in the option list but stays selected, and
                        // would silently save a mismatch.
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('data.city_id', null))
                        ->native(false),
                    Forms\Components\Select::make('data.city_id')
                        ->label(__('admin-panel.resources.ai_import.fields.city'))
                        ->options(fn (Forms\Get $get): array => filled($get('data.region_id'))
                            ? City::query()
                                ->where('region_id', $get('data.region_id'))
                                ->get()
                                ->mapWithKeys(fn (City $city): array => [$city->id => $city->localized_name])
                                ->all()
                            : [])
                        ->searchable()
                        ->disabled(fn (Forms\Get $get): bool => blank($get('data.region_id')))
                        ->native(false),
                    Forms\Components\TextInput::make('data.address')
                        ->label(__('admin-panel.resources.ai_import.fields.address'))
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('data.latitude')
                        ->label(__('admin-panel.resources.ai_import.fields.latitude'))
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('data.longitude')
                        ->label(__('admin-panel.resources.ai_import.fields.longitude'))
                        ->numeric()
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    /**
     * The minimum a category needs to be matchable.
     *
     * Every configured name is required: the resolver indexes every
     * translation, so a category missing one simply never matches a sheet
     * written in that language. Anything richer — icons, images, ordering —
     * belongs on ServiceCategoryResource, not in a modal inside a modal.
     *
     * @return array<int, Forms\Components\Component>
     */
    private static function categoryFields(): array
    {
        return array_map(
            static fn (string $locale): Forms\Components\TextInput => Forms\Components\TextInput::make("name.{$locale}")
                ->label(OfferLocales::label($locale))
                ->required()
                ->maxLength(255),
            OfferLocales::all(),
        );
    }

    /**
     * @return array<int, Forms\Components\Field>
     */
    private static function localizedOfferFields(string $field, bool $required = false): array
    {
        return array_map(
            static function (string $locale) use ($field, $required): Forms\Components\Field {
                $component = $field === 'title'
                    ? Forms\Components\TextInput::make("data.{$field}_translations.{$locale}")
                    : Forms\Components\Textarea::make("data.{$field}_translations.{$locale}")->rows(2);

                return $component
                    ->label(__('admin-panel.resources.ai_import.fields.'.$field).' — '.OfferLocales::label($locale))
                    ->required($required)
                    ->maxLength($field === 'title' ? 255 : 2000)
                    ->afterStateHydrated(function (Forms\Components\Field $component, mixed $state, ?AiImportRow $record) use ($field): void {
                        if (blank($state)) {
                            $component->state($record?->value($field));
                        }
                    })
                    ->columnSpanFull();
            },
            OfferLocales::all(),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function createCategory(array $data): int
    {
        $category = ServiceCategory::create([
            'name' => $data['name'],
            'applies_to' => 'both',
            'is_active' => true,
            // Unique among active rows, so it cannot simply default to 0.
            'sort_order' => (int) ServiceCategory::query()->max('sort_order') + 1,
        ]);

        return (int) $category->getKey();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('row_index')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\IconColumn::make('valid')
                    ->label('')
                    ->getStateUsing(fn (AiImportRow $record): bool => $record->isValid())
                    ->boolean(),
                Tables\Columns\TextColumn::make('label')
                    ->label(__('admin-panel.resources.ai_import.fields.person_name'))
                    ->description(fn (AiImportRow $record): ?string => $record->value('company'))
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('admin-panel.resources.ai_import.fields.phone'))
                    ->getStateUsing(fn (AiImportRow $record): ?string => $record->value('phone')
                        ?? $record->value('phone_raw'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('group_label')
                    ->label(__('admin-panel.resources.ai_import.fields.group_label'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('admin-panel.resources.ai_import.fields.category'))
                    // Falls back to the raw name so an unmatched row still
                    // shows what the sheet actually said.
                    ->getStateUsing(fn (AiImportRow $record): ?string => $record->value('category_id')
                        ? ServiceCategory::find($record->value('category_id'))?->localized_name
                        : $record->value('category_name'))
                    // "Created for review" outranks the rest: a category that
                    // does not exist for customers yet is the thing an admin
                    // most needs to notice before publishing. An alias match
                    // needs no flag — only a human's own correction is stored
                    // there now.
                    ->description(fn (AiImportRow $record): ?string => match (true) {
                        $record->value('category_created') === true => __('admin-panel.resources.ai_import.fields.category_created'),
                        $record->value('category_matched_by') === 'ai' => __('admin-panel.resources.ai_import.fields.model_used'),
                        default => null,
                    })
                    ->color(fn (AiImportRow $record): ?string => match (true) {
                        $record->value('category_id') === null => 'danger',
                        $record->value('category_created') === true => 'warning',
                        $record->value('category_matched_by') === 'ai' => 'warning',
                        default => null,
                    })
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin-panel.resources.ai_import.fields.title'))
                    ->getStateUsing(fn (AiImportRow $record): ?string => $record->value('title_translations.'.app()->getLocale())
                        ?? collect(OfferLocales::all())->map(fn (string $locale): mixed => $record->value('title_translations.'.$locale))->first(fn (mixed $value): bool => filled($value))
                        ?? $record->value('title'))
                    ->limit(40)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('region')
                    ->label(__('admin-panel.resources.ai_import.fields.region'))
                    ->getStateUsing(fn (AiImportRow $record): ?string => $record->value('region_id')
                        ? Region::find($record->value('region_id'))?->localized_name
                        : $record->value('region_name'))
                    ->color(fn (AiImportRow $record): ?string => $record->value('region_id') === null ? 'warning' : null)
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('admin-panel.resources.ai_import.fields.price'))
                    ->getStateUsing(fn (AiImportRow $record): string => $record->value('price_negotiable')
                        ? (string) ($record->value('price_raw') ?? '—')
                        : (string) $record->value('price'))
                    ->badge(fn (AiImportRow $record): bool => (bool) $record->value('price_negotiable'))
                    ->color(fn (AiImportRow $record): ?string => $record->value('price_negotiable') ? 'gray' : null)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('problems')
                    ->label(__('admin-panel.resources.ai_import.fields.problems'))
                    ->getStateUsing(fn (AiImportRow $record): array => $record->problemLabels())
                    ->badge()
                    ->color(fn (AiImportRow $record): string => $record->isValid() ? 'warning' : 'danger')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin-panel.resources.ai_import.fields.row_status'))
                    ->formatStateUsing(fn (AiImportRowStatus $state): string => $state->label())
                    ->color(fn (AiImportRowStatus $state): string => $state->color())
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('problems_only')
                    ->label(__('admin-panel.resources.ai_import.filters.problems_only'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('errors')
                        ->whereJsonLength('errors->blocking', '>', 0))
                    ->toggle(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('admin-panel.resources.ai_import.fields.row_status'))
                    ->options(AiImportRowStatus::options())
                    ->native(false),
            ])
            ->actions([
                // A plain Action rather than EditAction: Filament authorises
                // EditAction on `! isReadOnly()`, and the parent isReadOnly()
                // is true for every relation manager on a ViewRecord page
                // (readOnlyRelationManagersOnResourceViewPagesByDefault
                // defaults to true). That silently removed the only way to
                // correct a row once its batch was published — which is how a
                // partial publish used to strand every row it left behind.
                // A bare Action skips that gate, so the guard below is the
                // whole rule, visible where a reader looks for it.
                Tables\Actions\Action::make('edit_row')
                    ->label(__('admin-panel.resources.ai_import.actions.edit_row'))
                    ->icon('heroicon-m-pencil-square')
                    ->modalHeading(__('admin-panel.resources.ai_import.actions.edit_row_modal_heading'))
                    ->modalSubmitActionLabel(__('admin-panel.resources.ai_import.actions.edit_row_submit'))
                    ->successNotificationTitle(__('admin-panel.resources.ai_import.notifications.row_updated'))
                    // Published rows already created a real user, profile and
                    // offer; editing their staged copy would only desync it.
                    ->visible(fn (AiImportRow $record): bool => $record->status === AiImportRowStatus::Draft)
                    ->form(fn (Form $form): Form => $this->form($form->columns(2)))
                    // EditAction seeds the form from the record for you; a bare
                    // Action seeds it from nothing, and an empty modal would
                    // blank every field on save.
                    ->fillForm(fn (AiImportRow $record): array => ['data' => (array) $record->data])
                    ->action(fn (AiImportRow $record, array $data) => $this->applyCorrection($record, $data)),
                Tables\Actions\Action::make('skip')
                    ->label(__('admin-panel.resources.ai_import.actions.skip'))
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->visible(fn (AiImportRow $record): bool => $record->status === AiImportRowStatus::Draft)
                    ->action(fn (AiImportRow $record) => $record->forceFill(['status' => AiImportRowStatus::Skipped])->save()),
                Tables\Actions\Action::make('restore_row')
                    ->label(__('admin-panel.resources.ai_import.actions.restore'))
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (AiImportRow $record): bool => $record->status === AiImportRowStatus::Skipped)
                    ->action(fn (AiImportRow $record) => $record->forceFill(['status' => AiImportRowStatus::Draft])->save()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('skip_selected')
                        ->label(__('admin-panel.resources.ai_import.actions.skip'))
                        ->icon('heroicon-m-x-mark')
                        ->requiresConfirmation()
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records
                            // Same rule as the per-row action: a published row
                            // has already created records, so marking it
                            // skipped would only misreport what happened.
                            ->filter(fn (AiImportRow $record): bool => $record->status === AiImportRowStatus::Draft)
                            ->each(fn (AiImportRow $record) => $record
                                ->forceFill(['status' => AiImportRowStatus::Skipped])
                                ->save())),
                ]),
            ])
            ->defaultSort('row_index')
            ->paginated([25, 50, 100]);
    }

    /**
     * Save an admin's corrections, clear the errors they resolved, and
     * remember the taxonomy choices so the next import benefits.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyCorrection(AiImportRow $record, array $data): AiImportRow
    {
        $before = (array) $record->data;
        $after = [...$before, ...(array) ($data['data'] ?? [])];

        $this->rememberChoice(
            AiImportAlias::TAXONOMY_SERVICE_CATEGORY,
            $before['category_name'] ?? null,
            $before['category_id'] ?? null,
            $after['category_id'] ?? null,
        );

        $this->rememberChoice(
            AiImportAlias::TAXONOMY_REGION,
            $before['region_name'] ?? null,
            $before['region_id'] ?? null,
            $after['region_id'] ?? null,
        );

        if (($after['category_id'] ?? null) !== ($before['category_id'] ?? null)) {
            $after['category_matched_by'] = 'manual';
        }

        if (($after['region_id'] ?? null) !== ($before['region_id'] ?? null)) {
            $after['location_matched_by'] = 'manual';
        }

        $record->forceFill([
            'data' => $after,
            'label' => $after['person_name'] ?? $after['company'] ?? $after['phone'] ?? $record->label,
            'dedupe_key' => $after['phone'] ?? $record->dedupe_key,
            'errors' => $this->recheck($after),
        ])->save();

        return $record;
    }

    /**
     * Teach the alias table, but only when the admin actually decided
     * something — re-saving a row without touching the category should not
     * write a mapping the AI already made.
     */
    private function rememberChoice(string $taxonomy, mixed $sourceName, mixed $before, mixed $after): void
    {
        if (! is_string($sourceName) || $sourceName === '' || $after === null || $before === $after) {
            return;
        }

        AiImportAlias::remember(
            $taxonomy,
            $sourceName,
            (int) $after,
            AiImportAlias::ORIGIN_ADMIN,
            auth()->id(),
        );
    }

    /**
     * Re-derive the errors from the corrected values, so a row the admin fixed
     * stops being red without waiting for a re-parse.
     *
     * Delegates to the same validator the import itself used. An earlier copy
     * of these rules lived here and drifted, quietly discarding the region,
     * district and experience warnings the moment anyone opened a row.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, string>>
     */
    private function recheck(array $data): array
    {
        return (new WorkerRowValidator)->validate($data);
    }
}
