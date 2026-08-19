<x-filament-panels::page>
    <form wire:submit="calculate">
        {{ $this->form }}

        @if($this->calculationResult)
            <div class="mt-6 space-y-6">
                {{-- Crop Info --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-lg font-medium mb-2">
                        {{ $this->calculationResult['crop']['name']['en'] ?? $this->calculationResult['crop']['name']['ru'] ?? '' }}
                    </h3>
                    <div class="flex gap-6 text-sm text-gray-600 dark:text-gray-400">
                        <span>Baseline Yield: {{ $this->calculationResult['crop']['baseline_yield'] }} t/ha</span>
                        <span>Growing Cycle: {{ $this->calculationResult['crop']['cycle_days'] }} days</span>
                    </div>
                </div>

                {{-- Yield Result --}}
                <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-6 text-center">
                    <h2 class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        Potential Yield: {{ $this->calculationResult['potential_yield'] }} t/ha
                    </h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Yield Factor: {{ $this->calculationResult['yield_factor'] }}
                    </p>
                </div>

                {{-- Factor Details --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-4 border-b dark:border-gray-700">
                        <h3 class="text-lg font-medium">Calculation Factors</h3>
                    </div>
                    <div class="p-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="px-2 py-2 text-left">Parameter</th>
                                    <th class="px-2 py-2 text-center">Your Value</th>
                                    <th class="px-2 py-2 text-center">Optimal</th>
                                    <th class="px-2 py-2 text-center">Factor</th>
                                    <th class="px-2 py-2 text-center">Impact</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->calculationResult['factors'] as $key => $factor)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="px-2 py-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                        <td class="px-2 py-2 text-center">
                                            {{ $factor['value'] }}
                                            @if(isset($factor['unit']) && $factor['unit'])
                                                {{ $factor['unit'] }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            @if(isset($factor['optimal_value']))
                                                {{ $factor['optimal_value'] }}
                                            @elseif(isset($factor['optimal_range']))
                                                {{ $factor['optimal_range'] }}
                                            @else
                                                -
                                            @endif
                                            @if(isset($factor['unit']) && $factor['unit'])
                                                {{ $factor['unit'] }}
                                            @endif
                                        </td>
                                        <td class="px-2 py-2 text-center font-semibold">
                                            <span class="
                                                @if($factor['factor'] >= 0.9) text-green-600 dark:text-green-400
                                                @elseif($factor['factor'] >= 0.7) text-yellow-600 dark:text-yellow-400
                                                @elseif($factor['factor'] >= 0.5) text-orange-600 dark:text-orange-400
                                                @else text-red-600 dark:text-red-400
                                                @endif
                                            ">
                                                {{ number_format($factor['factor'], 3) }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-24">
                                                <div class="
                                                    @if($factor['factor'] >= 0.9) bg-green-600
                                                    @elseif($factor['factor'] >= 0.7) bg-yellow-600
                                                    @elseif($factor['factor'] >= 0.5) bg-orange-600
                                                    @else bg-red-600
                                                    @endif
                                                    h-2 rounded-full transition-all" 
                                                     style="width: {{ $factor['factor'] * 100 }}%">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Agro Scoring --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-4 border-b dark:border-gray-700">
                        <h3 class="text-lg font-medium">Agro Scoring</h3>
                    </div>
                    <div class="p-4">
                        {{-- Total Score --}}
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-lg font-medium">Total Score</span>
                                <span class="text-2xl font-bold">
                                    {{ $this->calculationResult['total_score']['score'] }}/{{ $this->calculationResult['total_score']['max_score'] }}
                                </span>
                            </div>
                            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                <div class="
                                    @if($this->calculationResult['total_score']['percentage'] >= 90) bg-green-600
                                    @elseif($this->calculationResult['total_score']['percentage'] >= 70) bg-yellow-600
                                    @elseif($this->calculationResult['total_score']['percentage'] >= 50) bg-orange-600
                                    @else bg-red-600
                                    @endif
                                    h-4 rounded-full transition-all flex items-center justify-center text-white text-xs font-medium"
                                     style="width: {{ $this->calculationResult['total_score']['percentage'] }}%">
                                    {{ $this->calculationResult['total_score']['percentage'] }}%
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <span class="text-lg font-semibold">{{ $this->calculationResult['total_score']['rating'] }}</span>
                            </div>
                        </div>

                        {{-- Individual Parameter Scoring --}}
                        <div class="space-y-3">
                            @foreach($this->calculationResult['scoring'] as $param => $scoring)
                                <div class="p-3 border dark:border-gray-700 rounded-lg">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ ucfirst(str_replace('_', ' ', $param)) }}</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Factor: {{ $scoring['factor'] }} | Weight: {{ $scoring['weight'] }}%
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold">{{ $scoring['score'] }}/{{ $scoring['weight'] }}</div>
                                            <div class="text-sm">{{ $scoring['rating']['label'] }}</div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="
                                            @if($scoring['rating']['status'] === 'excellent') bg-green-600
                                            @elseif($scoring['rating']['status'] === 'good') bg-green-500
                                            @elseif($scoring['rating']['status'] === 'average') bg-yellow-600
                                            @elseif($scoring['rating']['status'] === 'poor') bg-orange-600
                                            @else bg-red-600
                                            @endif
                                            h-2 rounded-full transition-all"
                                             style="width: {{ ($scoring['score'] / $scoring['weight']) * 100 }}%">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6 flex gap-3">
            <x-filament::button type="submit">
                Calculate Yield
            </x-filament::button>
            <x-filament::button type="button" wire:click="resetForm" color="gray">
                Reset
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>