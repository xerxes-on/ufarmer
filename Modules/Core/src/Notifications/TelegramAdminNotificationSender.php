<?php

declare(strict_types=1);

namespace Modules\Core\Notifications;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class TelegramAdminNotificationSender
{
    public function send(#[\SensitiveParameter] string $botToken, string $chatId, ?int $threadId, string $message): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'disable_web_page_preview' => true,
        ];

        if ($threadId !== null) {
            $payload['message_thread_id'] = $threadId;
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(5)
                ->timeout(15)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);
        } catch (Throwable) {
            throw new RuntimeException('Telegram sendMessage connection failed.');
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            throw new RuntimeException('Telegram sendMessage request failed with HTTP '.$response->status().'.');
        }
    }
}
