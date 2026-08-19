<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="mb-3 text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('admin-panel.pages.agronom_settings.current_fallback') }}</p>
            <img
                src="{{ $currentImageUrl }}"
                alt="{{ __('admin-panel.pages.agronom_settings.current_image_alt') }}"
                class="aspect-square w-full rounded-full object-cover"
            />
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit">
                {{ __('admin-panel.pages.agronom_settings.save_action') }}
            </x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
