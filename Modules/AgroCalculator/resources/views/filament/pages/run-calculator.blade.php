<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $parameterOverview = $this->parameterOverview ?? [];
            $defaults = $parameterOverview['defaults'] ?? [];
            $weights = $parameterOverview['weights'] ?? [];
            $factors = $parameterOverview['factors'] ?? [];
            $result = $this->result ?? null;

            $formatValue = static function ($value): string {
                if (is_array($value)) {
                    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                if (is_float($value) || is_int($value)) {
                    return number_format((float) $value, 4, '.', ' ');
                }

                return (string) $value;
            };
        @endphp

        @if($parameterOverview !== [])
            <div class="space-y-4 rounded-xl border border-gray-200/70 bg-white p-6 shadow-sm dark:border-gray-700/70 dark:bg-gray-900">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.summary.version') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $parameterOverview['version'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.summary.valid_from') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $parameterOverview['valid_from'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.summary.valid_to') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $parameterOverview['valid_to'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.summary.baseline_yield') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $parameterOverview['baseline_yield'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.summary.cycle_days') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $parameterOverview['cycle_days'] ?? '—' }}</p>
                    </div>
                </div>

                @if(!empty($defaults))
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('agrocalculator::filament.pages.run_calculator.summary.defaults') }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($defaults as $key => $value)
                                <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    <p class="font-medium">{{ \Illuminate\Support\Str::headline((string) $key) }}</p>
                                    <pre class="mt-2 whitespace-pre-wrap text-xs">{{ $formatValue($value) }}</pre>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($weights))
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('agrocalculator::filament.pages.run_calculator.summary.weights') }}</h3>
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.summary.factor') }}</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.summary.weight') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach($weights as $name => $value)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ \Illuminate\Support\Str::headline((string) $name) }}</td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ $formatValue($value) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(!empty($factors))
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('agrocalculator::filament.pages.run_calculator.summary.factors') }}</h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.summary.factor') }}</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.summary.type') }}</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.summary.config') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach($factors as $factor)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ \Illuminate\Support\Str::headline((string) ($factor['name'] ?? '—')) }}</td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) ($factor['type'] ?? '—'))) }}</td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">
                                                <span class="block text-xs leading-relaxed">{{ $factor['summary'] ?? '' }}</span>
                                                @if(!empty($factor['config']))
                                                    <pre class="mt-2 whitespace-pre-wrap text-xs">{{ $formatValue($factor['config']) }}</pre>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{ $this->form }}

        @if($result)
            @php
                $calculatorRun = $result['calculator_run'] ?? [];
                $scoring = $result['scoring'] ?? [];
                $recommendations = $result['recommendations'] ?? [];
                $factorResults = $calculatorRun['factors'] ?? [];
                if (array_values($factorResults) === $factorResults) {
                    $factorResults = collect($factorResults)
                        ->mapWithKeys(fn ($factor, $index) => [is_string($factor['name'] ?? null) ? $factor['name'] : (string) $index => $factor])
                        ->toArray();
                }
            @endphp
            <div class="space-y-4 rounded-xl border border-gray-200/70 bg-white p-6 shadow-sm dark:border-gray-700/70 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('agrocalculator::filament.pages.run_calculator.sections.results') }}</h2>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.results.potential_yield') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $calculatorRun['potential_yield_t_ha'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.results.stress_index') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $calculatorRun['stress_index'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.results.risk_level') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $calculatorRun['risk_level'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.results.score') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $scoring['score'] ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.results.grade') }}</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $scoring['grade'] ?? '—' }}</p>
                    </div>
                </div>

                @if(!empty($factorResults))
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('agrocalculator::filament.pages.run_calculator.results.factors') }}</h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.summary.factor') }}</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.results.score') }}</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.results.value') }}</th>
                                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('agrocalculator::filament.pages.run_calculator.results.context') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                    @foreach($factorResults as $name => $factor)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ \Illuminate\Support\Str::headline((string) $name) }}</td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ $factor['score'] ?? '—' }}</td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ $factor['value'] ?? '—' }}</td>
                                            <td class="px-4 py-2 text-gray-800 dark:text-gray-100">{{ \Illuminate\Support\Str::headline((string) ($factor['context'] ?? '—')) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(!empty($scoring['metrics'] ?? []))
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('agrocalculator::filament.pages.run_calculator.results.metrics') }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($scoring['metrics'] as $key => $value)
                                <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                    <p class="font-medium">{{ \Illuminate\Support\Str::headline((string) $key) }}</p>
                                    <pre class="mt-2 whitespace-pre-wrap text-xs">{{ $formatValue($value) }}</pre>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($scoring['flags'] ?? []))
                    <div class="space-y-2">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('agrocalculator::filament.pages.run_calculator.results.flags') }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($scoring['flags'] as $flag)
                                <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
                                    <p class="font-semibold">{{ $flag['code'] ?? '—' }}</p>
                                    <p class="text-xs uppercase tracking-wide">{{ \Illuminate\Support\Str::headline((string) ($flag['severity'] ?? '')) }}</p>
                                    @if(!empty($flag['description'] ?? null))
                                        <p class="mt-2 text-xs">{{ $flag['description'] }}</p>
                                    @endif
                                    @if(!empty($flag['context'] ?? null))
                                        <pre class="mt-2 whitespace-pre-wrap text-xs">{{ $formatValue($flag['context']) }}</pre>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="space-y-2">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('agrocalculator::filament.pages.run_calculator.results.recommendations') }}</h3>
                    @if(!empty($recommendations))
                        <ol class="space-y-2">
                            @foreach($recommendations as $index => $recommendation)
                                <li class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-200">
                                    <span class="font-semibold">{{ $index + 1 }}.</span>
                                    <span class="ml-1">{{ is_array($recommendation) ? $formatValue($recommendation) : (string) $recommendation }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('agrocalculator::filament.pages.run_calculator.results.no_recommendations') }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
