<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\AppSetting;

final class AdminNotificationChannelSettings
{
    public const string SETTING_KEY = 'admin_notification_channels';

    public const string AI_WORKER_REQUEST_EVENT = 'services.ai_worker.request_created';

    public const string AI_WORKER_CHAT_MESSAGE_EVENT = 'services.ai_worker.chat_message_received';

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $encrypted = AppSetting::query()
            ->where('key', self::SETTING_KEY)
            ->value('value');

        if (! is_string($encrypted) || $encrypted === '') {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            report($exception);

            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_array'));
    }

    /** @return list<array<string, mixed>> */
    public function forEvent(string $eventKey): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $channel): bool => ($channel['enabled'] ?? false) === true
                && self::channelAcceptsEvent($channel, $eventKey),
        ));
    }

    /** @param array<string, mixed> $channel */
    private static function channelAcceptsEvent(array $channel, string $eventKey): bool
    {
        $eventKeys = is_array($channel['event_keys'] ?? null) ? $channel['event_keys'] : [];

        if (in_array($eventKey, $eventKeys, true)) {
            return true;
        }

        // Existing service-operation destinations subscribed only to the
        // original request event. Route the related chat fallback there too,
        // without requiring a production settings rewrite during deployment.
        return $eventKey === self::AI_WORKER_CHAT_MESSAGE_EVENT
            && in_array(self::AI_WORKER_REQUEST_EVENT, $eventKeys, true);
    }

    /** @return array<string, mixed>|null */
    public function findEnabled(string $channelId, string $eventKey): ?array
    {
        foreach ($this->forEvent($eventKey) as $channel) {
            if (($channel['id'] ?? null) === $channelId) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $input
     * @return list<array<string, mixed>>
     */
    public function save(array $input): array
    {
        $existing = collect($this->all())->keyBy('id');
        $channels = [];
        $usedIds = [];

        foreach ($input as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = is_string($item['id'] ?? null) && Str::isUuid($item['id'])
                ? $item['id']
                : Str::uuid()->toString();

            if (isset($usedIds[$id])) {
                $id = Str::uuid()->toString();
            }
            $usedIds[$id] = true;

            $token = trim((string) ($item['bot_token'] ?? ''));
            if ($token === '') {
                $token = (string) data_get($existing->get($id), 'bot_token', '');
            }

            $eventKeys = array_values(array_unique(array_filter(
                array_map(
                    static fn (mixed $key): string => trim((string) $key),
                    is_array($item['event_keys'] ?? null) ? $item['event_keys'] : [],
                ),
                static fn (string $key): bool => $key !== '',
            )));

            $channel = [
                'id' => $id,
                'name' => trim((string) ($item['name'] ?? '')),
                'event_keys' => $eventKeys,
                'bot_token' => $token,
                'chat_id' => trim((string) ($item['chat_id'] ?? '')),
                'thread_id' => filled($item['thread_id'] ?? null) ? (int) $item['thread_id'] : null,
                'enabled' => (bool) ($item['enabled'] ?? false),
            ];

            $validator = validator($channel, [
                'id' => ['required', 'uuid'],
                'name' => ['required', 'string', 'max:100'],
                'event_keys' => ['required', 'array', 'min:1'],
                'event_keys.*' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9._-]+$/'],
                'bot_token' => ['required', 'string', 'max:255', 'regex:/^\d+:[A-Za-z0-9_-]+$/'],
                'chat_id' => ['required', 'string', 'max:33', 'regex:/^(?:-?\d+|@[A-Za-z][A-Za-z0-9_]{4,31})$/'],
                'thread_id' => ['nullable', 'integer', 'min:1'],
                'enabled' => ['boolean'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages(collect($validator->errors()->toArray())
                    ->mapWithKeys(fn (array $messages, string $field): array => [
                        "data.channels.{$index}.{$field}" => $messages,
                    ])->all());
            }

            $channels[] = $channel;
        }

        AppSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value_type' => AppSetting::TYPE_STRING,
                'value' => Crypt::encryptString(json_encode($channels, JSON_THROW_ON_ERROR)),
                'group' => AppSetting::GROUP_APP,
                'description' => [
                    'en' => 'Encrypted routing and credentials for internal admin notifications.',
                ],
                'enum_options' => null,
                'is_public' => false,
            ],
        );

        return $channels;
    }
}
