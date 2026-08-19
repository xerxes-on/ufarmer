<?php

declare(strict_types=1);

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Core\Notifications\TelegramAdminNotificationSender;
use Modules\Core\Support\AdminNotificationChannelSettings;
use Throwable;

final class SendTelegramAdminNotification implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 8;

    public int $timeout = 25;

    public int $uniqueFor = 86400;

    public function __construct(
        public readonly string $channelId,
        public readonly string $eventKey,
        public readonly string $eventId,
        public readonly string $message,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600, 7200, 14400];
    }

    public function uniqueId(): string
    {
        return hash('sha256', $this->channelId.'|'.$this->eventKey.'|'.$this->eventId);
    }

    public function handle(
        AdminNotificationChannelSettings $settings,
        TelegramAdminNotificationSender $sender,
    ): void {
        $channel = $settings->findEnabled($this->channelId, $this->eventKey);

        if ($channel === null) {
            return;
        }

        $sender->send(
            botToken: (string) $channel['bot_token'],
            chatId: (string) $channel['chat_id'],
            threadId: isset($channel['thread_id']) ? (int) $channel['thread_id'] : null,
            message: $this->message,
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('[SendTelegramAdminNotification] delivery exhausted retries', [
            'channel_id' => $this->channelId,
            'event_key' => $this->eventKey,
            'event_id' => $this->eventId,
            'error' => $exception->getMessage(),
        ]);
    }
}
