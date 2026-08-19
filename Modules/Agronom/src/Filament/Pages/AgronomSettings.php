<?php

declare(strict_types=1);

namespace Modules\Agronom\Filament\Pages;

use App\Filament\NavigationGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Agronom\Support\DefaultAgronomProfileImage;

final class AgronomSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = NavigationGroup::ServicesAndJobs->value;

    protected static ?int $navigationSort = 51;

    protected static ?string $slug = 'agronom/settings';

    protected static string $view = 'agronom::filament.pages.settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public string $currentImageUrl = '';

    public static function getNavigationLabel(): string
    {
        return __('admin-panel.pages.agronom_settings.navigation_label');
    }

    public function mount(DefaultAgronomProfileImage $setting): void
    {
        $value = $setting->value();
        $this->currentImageUrl = $setting->url();
        $this->form->fill([
            'default_profile_image' => ($value['source'] ?? null) === 'storage'
                ? ($value['path'] ?? null)
                : null,
        ]);
    }

    public function getHeading(): string
    {
        return __('admin-panel.pages.agronom_settings.heading');
    }

    public function getSubheading(): ?string
    {
        return __('admin-panel.pages.agronom_settings.subheading');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('admin-panel.pages.agronom_settings.section'))
                    ->description(__('admin-panel.pages.agronom_settings.description'))
                    ->schema([
                        FileUpload::make('default_profile_image')
                            ->label(__('admin-panel.pages.agronom_settings.replacement_image'))
                            ->disk(DefaultAgronomProfileImage::STORAGE_DISK)
                            ->directory('defaults/agronom')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('800')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(DefaultAgronomProfileImage $setting): void
    {
        $data = $this->form->getState();
        $path = $data['default_profile_image'] ?? null;
        $path = is_array($path) ? reset($path) : $path;

        if (! is_string($path) || trim($path) === '' || ! $setting->setStoragePath($path)) {
            Notification::make()
                ->title(__('admin-panel.pages.agronom_settings.save_failed'))
                ->danger()
                ->send();

            return;
        }

        $this->currentImageUrl = $setting->url();

        Notification::make()
            ->title(__('admin-panel.pages.agronom_settings.saved'))
            ->success()
            ->send();
    }

    public function restoreBuiltIn(DefaultAgronomProfileImage $setting): void
    {
        if (! $setting->useBuiltIn()) {
            Notification::make()
                ->title(__('admin-panel.pages.agronom_settings.restore_failed'))
                ->danger()
                ->send();

            return;
        }

        $this->currentImageUrl = $setting->url();
        $this->form->fill(['default_profile_image' => null]);

        Notification::make()
            ->title(__('admin-panel.pages.agronom_settings.restored'))
            ->success()
            ->send();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('restoreBuiltIn')
                ->label(__('admin-panel.pages.agronom_settings.restore_action'))
                ->requiresConfirmation()
                ->action('restoreBuiltIn'),
        ];
    }
}
