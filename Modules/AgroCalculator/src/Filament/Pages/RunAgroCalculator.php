<?php

declare(strict_types=1);

namespace Modules\AgroCalculator\Filament\Pages;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\AgroCalculator\Http\Requests\RunCalculationRequest;
use Modules\AgroCalculator\Http\Resources\CalculationResultResource;
use Modules\AgroCalculator\Jobs\RunCalculatorJob;
use Modules\AgroCalculator\Models\CropParameterSet;
use Modules\AgroCalculator\Services\ParameterResolver;
use Modules\Core\Models\AreaCrop;
use Throwable;

final class RunAgroCalculator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $slug = 'agro-calculator/tool';

    protected static ?string $navigationGroup = null;

    protected static string $view = 'agrocalculator::filament.pages.run-calculator';

    /**
     * @var array<string, mixed>
     */
    public array $formData = [
        'crop_id' => null,
        'area_crop_id' => null,
        'inputs' => [],
    ];

    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    public array $inputDefinitions = [];

    /**
     * @var array<string, mixed>
     */
    public array $parameterOverview = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $result = null;

    private ParameterResolver $parameterResolver;

    public function mount(ParameterResolver $parameterResolver): void
    {
        $this->parameterResolver = $parameterResolver;
        $this->form->fill($this->formData);
    }

    public static function getNavigationLabel(): string
    {
        return __('agrocalculator::filament.pages.run_calculator.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('agrocalculator::filament.navigation_group');
    }

    public function getHeading(): string
    {
        return __('agrocalculator::filament.pages.run_calculator.title');
    }

    public function getSubheading(): ?string
    {
        return __('agrocalculator::filament.pages.run_calculator.description');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('agrocalculator::filament.pages.run_calculator.sections.configuration'))
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('crop_id')
                                ->label(__('agrocalculator::filament.pages.run_calculator.fields.crop'))
                                ->options(fn (): array => $this->getCropOptions())
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?int $state, Closure $set): void {
                                    $set('area_crop_id', null);
                                    $this->handleCropChanged($state);
                                }),
                            Select::make('area_crop_id')
                                ->label(__('agrocalculator::filament.pages.run_calculator.fields.area_crop'))
                                ->options(fn (Get $get): array => $this->getAreaCropOptions($get('crop_id')))
                                ->searchable()
                                ->required(),
                        ]),
                    ]),
                Section::make(__('agrocalculator::filament.pages.run_calculator.sections.inputs'))
                    ->schema(fn (): array => $this->buildInputComponents())
                    ->visible(fn (): bool => $this->hasInputDefinitions()),
            ])
            ->statePath('formData');
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('run-calculation')
                ->label(__('agrocalculator::filament.pages.run_calculator.actions.calculate'))
                ->submit('runCalculation')
                ->keyBindings(['mod+s'])
                ->button(),
        ];
    }

    public function runCalculation(): void
    {
        $data = $this->form->getState();

        $cropId = (int) ($data['crop_id'] ?? 0);
        if ($cropId <= 0) {
            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.validation.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.validation.crop'))
                ->danger()
                ->send();

            return;
        }

        $areaCropId = (int) ($data['area_crop_id'] ?? 0);
        if ($areaCropId <= 0) {
            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.validation.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.validation.area_crop'))
                ->danger()
                ->send();

            return;
        }

        if ($this->parameterOverview === []) {
            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.no_parameters.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.no_parameters.body'))
                ->danger()
                ->send();

            return;
        }

        $payload = $this->buildPayload($data);

        try {
            $resolved = $this->parameterResolver->resolve($cropId, $payload);

            $resultData = RunCalculatorJob::dispatchSync(
                areaCropId: $resolved->areaCrop->id,
                parameterSetId: $resolved->parameterSet->id,
                parameters: $resolved->parameters,
                inputs: $resolved->inputs,
                overrides: $resolved->overrides,
            );

            /** @var array<string, mixed> $resolvedResult */
            $resolvedResult = (new CalculationResultResource($resultData))->toArray(request());

            $this->result = $resolvedResult;

            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.calculation_success.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.calculation_success.body'))
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            $messages = $this->flattenErrorMessages($exception);

            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.validation.title'))
                ->body($messages)
                ->danger()
                ->send();

            $this->addError('runCalculation', $messages);
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.calculation_failed.title'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function handleCropChanged(?int $cropId): void
    {
        $this->result = null;
        $this->inputDefinitions = [];
        $this->parameterOverview = [];

        $this->formData['inputs'] = [];

        if ($cropId === null) {
            $this->form->fill($this->formData);

            return;
        }

        if (! $this->parameterSetTableExists()) {
            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.no_parameters.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.tables_missing'))
                ->warning()
                ->send();
            $this->form->fill($this->formData);

            return;
        }

        try {
            /** @var CropParameterSet|null $parameterSet */
            $parameterSet = CropParameterSet::query()
                ->where('crop_id', $cropId)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        } catch (QueryException $exception) {
            report($exception);

            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.no_parameters.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.tables_missing'))
                ->danger()
                ->send();

            $this->form->fill($this->formData);

            return;
        }

        if ($parameterSet === null) {
            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.no_parameters.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.no_parameters.body'))
                ->danger()
                ->send();

            $this->form->fill($this->formData);

            return;
        }

        $params = $parameterSet->params ?? [];

        $this->parameterOverview = $this->buildParameterOverview($parameterSet);
        $this->inputDefinitions = $this->buildInputDefinitions($params);

        $this->formData['inputs'] = $this->getDefaultInputState($this->inputDefinitions);
        $this->form->fill($this->formData);
    }

    private function hasInputDefinitions(): bool
    {
        return $this->inputDefinitions !== [];
    }

    private function buildPayload(array $data): array
    {
        $inputs = $this->normaliseInputs($data['inputs'] ?? []);

        $payload = [
            RunCalculationRequest::FIELD_CROP_ID => (int) ($data['crop_id'] ?? 0),
            RunCalculationRequest::FIELD_AREA_CROP_ID => (int) ($data['area_crop_id'] ?? 0),
            RunCalculationRequest::FIELD_INPUTS => $inputs,
        ];

        $sources = Arr::get($this->parameterOverview, 'params.inputs.sources', []);
        if (! is_array($sources)) {
            $sources = Arr::get($this->parameterOverview, 'inputs.sources', []);
        }

        if (! is_array($sources)) {
            $sources = [];
        }

        foreach ($sources as $category => $fieldName) {
            if (isset($inputs[$category])) {
                $payload[$fieldName] = $inputs[$category];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, array<string, mixed>>  $definitions
     * @return array<string, array<string, mixed|null>>
     */
    private function getDefaultInputState(array $definitions): array
    {
        $state = [];
        foreach ($definitions as $category => $fields) {
            foreach ($fields as $key => $definition) {
                $state[$category][$key] = $definition['default'] ?? null;
            }
        }

        return $state;
    }

    /**
     * @return array<int, Fieldset>
     */
    private function buildInputComponents(): array
    {
        if ($this->inputDefinitions === []) {
            return [];
        }

        $components = [];
        foreach ($this->inputDefinitions as $category => $fields) {
            $components[] = Fieldset::make($this->formatCategoryHeading($category))
                ->schema($this->buildFieldComponents($category, $fields))
                ->columns(2);
        }

        return $components;
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @return array<int, Select|TextInput>
     */
    private function buildFieldComponents(string $category, array $fields): array
    {
        $components = [];

        foreach ($fields as $key => $definition) {
            $fieldPath = "inputs.$category.$key";

            if (! empty($definition['options'])) {
                $component = Select::make($fieldPath)
                    ->label($definition['label'])
                    ->options($definition['options'])
                    ->searchable()
                    ->allowHtml(false)
                    ->nullable();
            } else {
                $component = TextInput::make($fieldPath)
                    ->label($definition['label'])
                    ->numeric()
                    ->nullable();
            }

            if (! empty($definition['unit'])) {
                $component->suffix((string) $definition['unit']);
            }

            if (! empty($definition['required'])) {
                $component->required();
            }

            if ($definition['default'] !== null && $definition['default'] !== '') {
                $component->default($definition['default']);
            }

            if (! empty($definition['helper'])) {
                $component->helperText($definition['helper']);
            }

            $components[] = $component;
        }

        return $components;
    }

    private function formatCategoryHeading(string $category): string
    {
        return Str::headline($category);
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function buildInputDefinitions(array $params): array
    {
        $definitions = [];
        $allKeys = [];
        $usage = [];

        $required = Arr::get($params, 'inputs.required', []);
        foreach ($required as $category => $keys) {
            foreach ((array) $keys as $key) {
                $allKeys[$category][$key] = true;
            }
        }

        $defaults = Arr::get($params, 'inputs.defaults', []);
        foreach ($defaults as $category => $values) {
            foreach (array_keys((array) $values) as $key) {
                $allKeys[$category][$key] = true;
            }
        }

        $optional = Arr::get($params, 'inputs.optional', []);
        foreach ($optional as $category => $keys) {
            foreach ((array) $keys as $key) {
                $allKeys[$category][$key] = true;
            }
        }

        $factors = Arr::get($params, 'factors', []);
        foreach ((array) $factors as $factor) {
            $this->extractInputReferences($factor, $allKeys, $usage, (array) $factor);
        }

        foreach ($usage as $category => &$fields) {
            foreach ($fields as $key => &$entries) {
                $unique = [];
                foreach ($entries as $entry) {
                    $identifier = ($entry['name'] ?? '').'|'.($entry['type'] ?? '');
                    if (isset($unique[$identifier])) {
                        continue;
                    }

                    $unique[$identifier] = $entry;
                }

                $entries = array_values($unique);
            }
        }
        unset($fields, $entries);

        ksort($allKeys);
        foreach ($allKeys as $category => $keys) {
            ksort($keys);
            foreach (array_keys($keys) as $key) {
                $definitions[$category][$key] = $this->makeInputDefinition(
                    params: $params,
                    category: (string) $category,
                    key: (string) $key,
                    required: $required,
                    defaults: $defaults,
                    usage: $usage
                );
            }
        }

        return $definitions;
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, array<int, string>>  $required
     * @param  array<string, array<string, mixed>>  $defaults
     * @param  array<string, array<string, mixed>>  $usage
     * @return array<string, mixed>
     */
    private function makeInputDefinition(array $params, string $category, string $key, array $required, array $defaults, array $usage): array
    {
        $label = $this->resolveInputLabel($params, $category, $key);
        $description = $this->resolveInputDescription($params, $category, $key);
        $options = $this->resolveInputOptions($params, $category, $key);
        $unit = $this->resolveInputUnit($params, $category, $key);
        $default = Arr::get($defaults, "$category.$key");
        if ($default === '' || $default === []) {
            $default = null;
        }

        $factors = $usage[$category][$key] ?? [];
        $helper = $this->buildHelperText($description, $default, $options, $factors);

        return [
            'label' => $label,
            'required' => in_array($key, $required[$category] ?? [], true),
            'default' => $default,
            'description' => $description,
            'options' => $options,
            'factors' => $factors,
            'helper' => $helper,
            'unit' => $unit,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function resolveInputLabel(array $params, string $category, string $key): string
    {
        $label = Arr::get($params, "inputs.labels.$category.$key")
            ?? Arr::get($params, "meta.inputs.$category.$key.label")
            ?? Arr::get($params, "meta.inputs.$category.$key.name")
            ?? Arr::get($params, "meta.inputs.$category.$key.title");

        if ($label instanceof Arrayable) {
            $label = $label->toArray();
        }

        if (is_array($label)) {
            return $this->resolveLocalizedString($label);
        }

        if (is_string($label) && $label !== '') {
            return $label;
        }

        return Str::headline($key);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function resolveInputDescription(array $params, string $category, string $key): ?string
    {
        $description = Arr::get($params, "inputs.descriptions.$category.$key")
            ?? Arr::get($params, "meta.inputs.$category.$key.description");

        if ($description instanceof Arrayable) {
            $description = $description->toArray();
        }

        if (is_array($description)) {
            return $this->resolveLocalizedString($description);
        }

        return $description !== '' ? $description : null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, string>
     */
    private function resolveInputOptions(array $params, string $category, string $key): array
    {
        $options = Arr::get($params, "inputs.options.$category.$key")
            ?? Arr::get($params, "meta.inputs.$category.$key.options");

        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        if (! is_array($options)) {
            return [];
        }

        $resolved = [];

        if ($this->isAssociativeArray($options)) {
            foreach ($options as $value => $label) {
                $resolved[(string) $value] = $this->resolveLocalizedString($label);
            }

            return $resolved;
        }

        foreach ($options as $option) {
            if (is_array($option)) {
                $value = $option['value'] ?? $option['key'] ?? $option['code'] ?? null;
                if ($value === null) {
                    continue;
                }

                $label = $option['label'] ?? $option['name'] ?? $option['title'] ?? $value;
                $resolved[(string) $value] = $this->resolveLocalizedString($label);

                continue;
            }

            $resolved[(string) $option] = (string) $option;
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function resolveInputUnit(array $params, string $category, string $key): ?string
    {
        $unit = Arr::get($params, "inputs.units.$category.$key")
            ?? Arr::get($params, "meta.inputs.$category.$key.unit")
            ?? Arr::get($params, "meta.inputs.$category.$key.unit_label");

        if ($unit instanceof Arrayable) {
            $unit = $unit->toArray();
        }

        if (is_array($unit)) {
            return $this->resolveLocalizedString($unit);
        }

        return $unit !== '' ? (string) $unit : null;
    }

    /**
     * @param  array<string, mixed>  $factor
     */
    private function extractInputReferences(mixed $node, array &$collection, array &$usage, array $factor): void
    {
        if (! is_array($node)) {
            return;
        }

        if (isset($node['category'], $node['key'])) {
            $category = (string) $node['category'];
            $key = (string) $node['key'];

            $collection[$category][$key] = true;
            $usage[$category][$key][] = [
                'name' => $factor['name'] ?? null,
                'type' => $factor['type'] ?? null,
                'config' => $factor['config'] ?? [],
            ];
        }

        foreach ($node as $child) {
            $this->extractInputReferences($child, $collection, $usage, $factor);
        }
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, string>  $options
     * @param  array<int, array<string, mixed>>  $factors
     */
    private function buildHelperText(?string $description, mixed $default, array $options, array $factors): ?string
    {
        $parts = [];

        if ($description !== null && trim($description) !== '') {
            $parts[] = $description;
        }

        if ($default !== null && $default !== '') {
            $parts[] = __('agrocalculator::filament.pages.run_calculator.helper.default', [
                'value' => $default,
            ]);
        }

        if ($options !== []) {
            $optionsText = collect($options)
                ->map(fn ($label, $value): string => $label.' ('.$value.')')
                ->implode(', ');
            $parts[] = __('agrocalculator::filament.pages.run_calculator.helper.options', [
                'list' => $optionsText,
            ]);
        }

        if ($factors !== []) {
            $factorText = collect($factors)
                ->map(fn (array $factor): string => $this->formatFactorSummary($factor))
                ->implode('; ');
            $parts[] = __('agrocalculator::filament.pages.run_calculator.helper.factors', [
                'list' => $factorText,
            ]);
        }

        if ($parts === []) {
            return null;
        }

        return implode(PHP_EOL, $parts);
    }

    /**
     * @param  array<string, mixed>  $factor
     */
    private function formatFactorSummary(array $factor): string
    {
        $name = (string) ($factor['name'] ?? '—');
        $type = (string) ($factor['type'] ?? '—');
        $summary = $name;

        if ($type !== '—') {
            $summary .= ' ['.Str::headline(str_replace('_', ' ', $type)).']';
        }

        $config = is_array($factor['config'] ?? null) ? $factor['config'] : [];
        $configSummary = $this->summariseFactorConfig((string) ($factor['type'] ?? ''), $config);

        if ($configSummary !== null) {
            $summary .= ' — '.$configSummary;
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function summariseFactorConfig(string $type, array $config): ?string
    {
        return match ($type) {
            'band' => (isset($config['min'], $config['max']))
                ? __('agrocalculator::filament.pages.run_calculator.factor_summaries.range', [
                    'min' => $config['min'],
                    'max' => $config['max'],
                ])
                : null,
            'ratio_piecewise' => isset($config['optimal'])
                ? __('agrocalculator::filament.pages.run_calculator.factor_summaries.optimal', [
                    'value' => $config['optimal'],
                ])
                : null,
            'salinity' => isset($config['threshold'])
                ? __('agrocalculator::filament.pages.run_calculator.factor_summaries.threshold', [
                    'value' => $config['threshold'],
                ])
                : null,
            'water_balance' => isset($config['optimal'])
                ? __('agrocalculator::filament.pages.run_calculator.factor_summaries.target', [
                    'value' => $config['optimal'],
                ])
                : null,
            'temperature_triangle' => (isset($config['min'], $config['max']))
                ? __('agrocalculator::filament.pages.run_calculator.factor_summaries.range', [
                    'min' => $config['min'],
                    'max' => $config['max'],
                ])
                : null,
            'breakpoints' => isset($config['breakpoints'])
                ? __('agrocalculator::filament.pages.run_calculator.factor_summaries.breakpoints', [
                    'count' => is_countable($config['breakpoints']) ? count($config['breakpoints']) : 0,
                ])
                : null,
            'pressure' => __('agrocalculator::filament.pages.run_calculator.factor_summaries.pressure'),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParameterOverview(CropParameterSet $parameterSet): array
    {
        $params = $parameterSet->params ?? [];

        $overview = [
            'version' => $parameterSet->version,
            'valid_from' => $parameterSet->valid_from?->toDateString(),
            'valid_to' => $parameterSet->valid_to?->toDateString(),
            'baseline_yield' => Arr::get($params, 'baseline_yield'),
            'cycle_days' => Arr::get($params, 'cycle_days'),
            'defaults' => Arr::get($params, 'defaults', []),
            'weights' => Arr::get($params, 'weights', []),
            'params' => $params,
        ];

        $overview['factors'] = collect(Arr::get($params, 'factors', []))
            ->map(function ($factor): array {
                $factor = (array) $factor;

                return [
                    'name' => (string) ($factor['name'] ?? '—'),
                    'type' => (string) ($factor['type'] ?? '—'),
                    'config' => $factor['config'] ?? [],
                    'summary' => $this->formatFactorSummary($factor),
                ];
            })
            ->values()
            ->all();

        return $overview;
    }

    /**
     * @return array<int|string, string>
     */
    private function getCropOptions(): array
    {
        if (! $this->parameterSetTableExists()) {
            return [];
        }

        try {
            return CropParameterSet::query()
                ->with('crop')
                ->where('is_active', true)
                ->orderByDesc('version')
                ->get()
                ->unique('crop_id')
                ->mapWithKeys(function (CropParameterSet $set): array {
                    $crop = $set->crop;
                    $name = $crop !== null
                        ? ($crop->localized_name ?? $this->resolveLocalizedString($crop->name))
                        : __('agrocalculator::filament.pages.run_calculator.labels.unknown_crop');

                    return [
                        $crop?->id ?? $set->crop_id => sprintf('%s (v%s)', $name, $set->version),
                    ];
                })
                ->toArray();
        } catch (QueryException $exception) {
            report($exception);

            Notification::make()
                ->title(__('agrocalculator::filament.pages.run_calculator.notifications.no_parameters.title'))
                ->body(__('agrocalculator::filament.pages.run_calculator.notifications.tables_missing'))
                ->warning()
                ->send();

            return [];
        }
    }

    private function parameterSetTableExists(): bool
    {
        return Schema::hasTable('agro_calculator_crop_parameter_sets');
    }

    /**
     * @return array<int|string, string>
     */
    private function getAreaCropOptions(?int $cropId): array
    {
        $query = AreaCrop::query()
            ->with(['crop', 'area'])
            ->orderByDesc('id');

        if ($cropId !== null && $cropId > 0) {
            $query->where('crop_id', $cropId);
        }

        return $query
            ->limit(100)
            ->get()
            ->mapWithKeys(function (AreaCrop $areaCrop): array {
                $areaName = $areaCrop->area?->name ?? __('agrocalculator::filament.pages.run_calculator.labels.unknown_area');
                $cropName = $areaCrop->crop?->localized_name
                    ?? ($areaCrop->crop?->name !== null ? $this->resolveLocalizedString($areaCrop->crop->name) : __('agrocalculator::filament.pages.run_calculator.labels.unknown_crop'));

                $label = sprintf(
                    '%s • %s (ID: %d)',
                    $areaName,
                    $cropName,
                    $areaCrop->id
                );

                return [$areaCrop->id => $label];
            })
            ->toArray();
    }

    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|string|null  $value
     */
    private function resolveLocalizedString(mixed $value): string
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale', 'en');

            if (array_key_exists($locale, $value) && $value[$locale] !== null && $value[$locale] !== '') {
                return (string) $value[$locale];
            }

            if (array_key_exists($fallback, $value) && $value[$fallback] !== null && $value[$fallback] !== '') {
                return (string) $value[$fallback];
            }

            foreach ($value as $item) {
                if ($item !== null && $item !== '') {
                    return (string) $item;
                }
            }

            return '';
        }

        return $value !== null ? (string) $value : '';
    }

    /**
     * @param  array<string, array<string, mixed|null>>  $inputs
     * @return array<string, array<string, float|string|null>>
     */
    private function normaliseInputs(array $inputs): array
    {
        foreach ($inputs as $category => &$fields) {
            if (! is_array($fields)) {
                unset($inputs[$category]);

                continue;
            }

            foreach ($fields as $key => &$value) {
                if ($value === '' || $value === null) {
                    $value = null;

                    continue;
                }

                if (is_numeric($value)) {
                    $value = (float) $value;
                } else {
                    $value = (string) $value;
                }
            }
        }

        return $inputs;
    }

    private function flattenErrorMessages(ValidationException $exception): string
    {
        return collect($exception->errors())
            ->flatMap(fn ($messages) => (array) $messages)
            ->implode(PHP_EOL);
    }

    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
