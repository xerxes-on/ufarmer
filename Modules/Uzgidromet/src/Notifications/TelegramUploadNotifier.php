<?php

declare(strict_types=1);

namespace Modules\Uzgidromet\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Uzgidromet\Models\UzgidrometFile;

/**
 * Posts a single HTML-formatted message to the Uzgidromet logs channel
 * whenever a new forecast file lands. Reads bot + channel from
 * `services.telegram.uzgidromet`; silently no-ops when either is unset
 * so local/test environments don't need the secrets.
 */
final class TelegramUploadNotifier
{
    public function notifyUploaded(UzgidrometFile $file): void
    {
        $botToken = (string) config('services.telegram.uzgidromet.bot_token', '');
        $channelId = (string) config('services.telegram.uzgidromet.channel_id', '');

        if ($botToken === '' || $channelId === '') {
            return;
        }

        $text = $this->buildMessage($file);

        try {
            $response = Http::timeout(8)
                ->retry(2, 250)
                ->asJson()
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $channelId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if (! $response->successful() || $response->json('ok') !== true) {
                Log::warning('Telegram uzgidromet notify failed', [
                    'file_id' => $file->id,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }
        } catch (\Throwable $e) {
            // The Telegram channel is a notification side-channel — never
            // let its outage break the admin upload flow.
            Log::warning('Telegram uzgidromet notify threw', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildMessage(UzgidrometFile $file): string
    {
        $fileName = $this->escape((string) $file->original_name);
        $sizeHuman = $this->escape((string) $file->file_size_human);
        $mime = match ((string) $file->mime_type) {
            'application/pdf' => 'PDF',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            default => $this->escape((string) $file->mime_type),
        };

        $uploadedBy = $file->relationLoaded('uploadedBy')
            ? $file->uploadedBy
            : $file->uploadedBy()->first();
        $uploader = $this->escape(
            $uploadedBy?->name !== null && $uploadedBy->name !== ''
                ? (string) $uploadedBy->name
                : 'noma\'lum',
        );

        $when = $file->created_at?->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i')
            ?? now()->format('Y-m-d H:i');
        $when = $this->escape($when);

        $appUrl = rtrim((string) config('app.url', ''), '/');
        $linkLine = '';
        if ($appUrl !== '') {
            $url = $appUrl.'/uzgidromet/uzgidromet-files/'.$file->id.'/edit';
            $linkLine = "\n\n<a href=\"{$url}\">🔎 Panelda ko'rish</a>";
        }

        return <<<HTML
🌦️ <b>Yangi ob-havo prognozi yuklandi</b>

📄 <b>Fayl:</b> <code>{$fileName}</code>
🗂 <b>Turi:</b> {$mime}
📦 <b>Hajmi:</b> {$sizeHuman}
👤 <b>Yuklagan:</b> {$uploader}
🕐 <b>Vaqt:</b> {$when}{$linkLine}
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
