<?php

declare(strict_types=1);

namespace Modules\Core\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Core\Jobs\SendTelegramAdminNotification;
use Modules\Core\Support\AdminNotificationChannelSettings;

final class DispatchAdminNotification
{
    public function __construct(
        private readonly AdminNotificationChannelSettings $settings,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handleRaw(array $payload): void
    {
        $eventPayload = $payload['payload'] ?? [];
        $eventKey = is_array($eventPayload) ? ($eventPayload['event_key'] ?? null) : null;
        $eventId = is_array($eventPayload) ? ($eventPayload['event_id'] ?? null) : null;
        $text = is_array($eventPayload) ? ($eventPayload['message'] ?? null) : null;

        if (! is_string($eventKey) || $eventKey === ''
            || ! is_string($eventId) || $eventId === ''
            || ! is_string($text) || $text === '') {
            Log::warning('[DispatchAdminNotification] invalid notification event');

            return;
        }

        foreach ($this->settings->forEvent($eventKey) as $channel) {
            $channelId = $channel['id'] ?? null;

            if (! is_string($channelId) || $channelId === '') {
                continue;
            }

            SendTelegramAdminNotification::dispatch(
                channelId: $channelId,
                eventKey: $eventKey,
                eventId: $eventId,
                message: $text,
            );
        }
    }
}
