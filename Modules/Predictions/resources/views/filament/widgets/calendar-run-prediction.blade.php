<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Prediction Analysis
        </x-slot>

        @if($hasPrediction)
            {{-- Prediction Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                {{-- Predicted Yield --}}
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Predicted Yield</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $predictedYield ? number_format($predictedYield, 2) . ' t/ha' : '-' }}
                    </div>
                </div>

                {{-- Actual Yield --}}
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Actual Yield</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ $actualYield ? number_format($actualYield, 2) . ' t/ha' : '-' }}
                    </div>
                </div>

                {{-- Confidence --}}
                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Confidence</div>
                    <div class="text-2xl font-bold {{ $yieldConfidence >= 80 ? 'text-green-600' : ($yieldConfidence >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $yieldConfidence ? number_format($yieldConfidence, 0) . '%' : '-' }}
                    </div>
                </div>

                {{-- Days Until Harvest --}}
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Days Until Harvest</div>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                        {{ $daysUntilHarvest !== null ? $daysUntilHarvest . ' days' : '-' }}
                    </div>
                </div>
            </div>

            {{-- Accuracy Meter --}}
            @if($accuracyPct !== null)
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Prediction Accuracy</span>
                        <span class="text-sm font-bold {{ $accuracyPct >= 90 ? 'text-green-600' : ($accuracyPct >= 75 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($accuracyPct, 1) }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="h-3 rounded-full {{ $accuracyPct >= 90 ? 'bg-green-500' : ($accuracyPct >= 75 ? 'bg-yellow-500' : 'bg-red-500') }}"
                             style="width: {{ min($accuracyPct, 100) }}%"></div>
                    </div>
                </div>
            @endif

            {{-- Factors Breakdown --}}
            @if(count($factors) > 0)
                <div class="mt-6">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Prediction Factors</h4>
                    <div class="space-y-3">
                        @foreach($factors as $factor)
                            <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                @if($factor['image_url'])
                                    <img src="{{ $factor['image_url'] }}" alt="{{ $factor['name'] }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <x-heroicon-o-beaker class="w-5 h-5 text-gray-400"/>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $factor['name'] }}</div>
                                    @if($factor['description'])
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($factor['description'], 60) }}</div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-2 rounded-full bg-blue-500"
                                             style="width: {{ $maxBoost > 0 ? min(($factor['boost_pct'] / $maxBoost) * 100, 100) : 0 }}%"></div>
                                    </div>
                                    <span class="font-semibold text-sm w-12 text-right {{ $factor['boost_pct'] > 0 ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ $factor['boost_pct'] > 0 ? '+' : '' }}{{ number_format($factor['boost_pct'], 1) }}%
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Total Boost --}}
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex justify-between items-center">
                        <span class="font-medium text-blue-700 dark:text-blue-300">Total Factor Contribution</span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            {{ $totalBoost > 0 ? '+' : '' }}{{ number_format($totalBoost, 1) }}%
                        </span>
                    </div>
                </div>
            @else
                <div class="text-center py-4 text-gray-500">
                    No prediction factors recorded for this calendar run.
                </div>
            @endif

            {{-- Risk Assessment --}}
            @if($yieldConfidence !== null)
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Low Risk --}}
                    <div class="p-4 rounded-lg border-2 {{ $yieldConfidence >= 80 ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-check-circle class="w-5 h-5 {{ $yieldConfidence >= 80 ? 'text-green-500' : 'text-gray-400' }}"/>
                            <span class="font-semibold {{ $yieldConfidence >= 80 ? 'text-green-700 dark:text-green-300' : 'text-gray-500' }}">Low Risk</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">High confidence in prediction accuracy. Good growing conditions expected.</p>
                    </div>

                    {{-- Medium Risk --}}
                    <div class="p-4 rounded-lg border-2 {{ $yieldConfidence >= 60 && $yieldConfidence < 80 ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 {{ $yieldConfidence >= 60 && $yieldConfidence < 80 ? 'text-yellow-500' : 'text-gray-400' }}"/>
                            <span class="font-semibold {{ $yieldConfidence >= 60 && $yieldConfidence < 80 ? 'text-yellow-700 dark:text-yellow-300' : 'text-gray-500' }}">Medium Risk</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Moderate confidence. Some factors may affect yield.</p>
                    </div>

                    {{-- High Risk --}}
                    <div class="p-4 rounded-lg border-2 {{ $yieldConfidence < 60 ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <x-heroicon-o-x-circle class="w-5 h-5 {{ $yieldConfidence < 60 ? 'text-red-500' : 'text-gray-400' }}"/>
                            <span class="font-semibold {{ $yieldConfidence < 60 ? 'text-red-700 dark:text-red-300' : 'text-gray-500' }}">High Risk</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Low confidence. Significant variance from prediction expected.</p>
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-8">
                <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto text-gray-400 mb-3"/>
                <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">No Prediction Available</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">This calendar run does not have yield prediction data yet.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
