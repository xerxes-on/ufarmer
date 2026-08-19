<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Pages;

use App\Filament\NavigationGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Modules\Core\Support\AdminNotificationChannelSettings;

final class AdminNotificationChannels extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Admin notification channels';

    protected static ?string $navigationGroup = NavigationGroup::Administration->value;

    protected static ?int $navigationSort = 85;

    protected static ?string $slug = 'settings/admin-notification-channels';

    protected static string $view = 'core::filament.pages.admin-notification-channels';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(AdminNotificationChannelSettings $settings): void
    {
        $this->fillForm($settings->all());
    }

    public function getHeading(): string
    {
        return 'Admin notification channels';
    }

    public function getSubheading(): ?string
    {
        return 'Route internal events to Telegram groups, channels, or forum topics. Credentials are encrypted before they are stored.';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null
            && method_exists($user, 'hasRole')
            && $user->hasRole('super_admin');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Telegram destinations')
                    ->description('A destination can subscribe to one or many event keys. Add more rows to notify multiple groups or topics.')
                    ->schema([
                        Repeater::make('channels')
                            ->label('Channels')
                            ->schema([
                                Hidden::make('id')
                                    ->default(fn (): string => Str::uuid()->toString()),
                                Hidden::make('has_bot_token')
                                    ->default(false),
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Service operations group'),
                                Toggle::make('enabled')
                                    ->label('Enabled')
                                    ->default(true),
                                TagsInput::make('event_keys')
                                    ->label('Event keys')
                                    ->required()
                                    ->suggestions([
                                        AdminNotificationChannelSettings::AI_WORKER_REQUEST_EVENT,
                                        AdminNotificationChannelSettings::AI_WORKER_CHAT_MESSAGE_EVENT,
                                    ])
                                    ->default([
                                        AdminNotificationChannelSettings::AI_WORKER_REQUEST_EVENT,
                                        AdminNotificationChannelSettings::AI_WORKER_CHAT_MESSAGE_EVENT,
                                    ])
                                    ->helperText('Press Enter after each event key.'),
                                TextInput::make('bot_token')
                                    ->label('Telegram bot token')
                                    ->password()
                                    ->autocomplete('new-password')
                                    ->required(fn (Get $get): bool => ! (bool) $get('has_bot_token'))
                                    ->helperText(fn (Get $get): string => (bool) $get('has_bot_token')
                                        ? 'A token is already stored. Leave blank to keep it.'
                                        : 'Required for a new destination.'),
                                TextInput::make('chat_id')
                                    ->label('Chat or channel ID')
                                    ->required()
                                    ->maxLength(33)
                                    ->placeholder('-1003816518163'),
                                TextInput::make('thread_id')
                                    ->label('Forum topic/thread ID')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Optional'),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->addActionLabel('Add destination')
                            ->reorderable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(AdminNotificationChannelSettings $settings): void
    {
        $state = $this->form->getState();
        $channels = $settings->save(is_array($state['channels'] ?? null) ? $state['channels'] : []);

        $this->fillForm($channels);

        Notification::make()
            ->title('Admin notification channels saved')
            ->success()
            ->send();
    }

    /** @param list<array<string, mixed>> $channels */
    private function fillForm(array $channels): void
    {
        $this->form->fill([
            'channels' => array_map(static function (array $channel): array {
                $hasToken = filled($channel['bot_token'] ?? null);
                unset($channel['bot_token']);

                return [
                    ...$channel,
                    'bot_token' => null,
                    'has_bot_token' => $hasToken,
                ];
            }, $channels),
        ]);
    }
}
