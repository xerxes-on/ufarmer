<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Jobs\SendTelegramAdminNotification;
use Modules\Core\Listeners\DispatchAdminNotification;
use Modules\Core\Notifications\TelegramAdminNotificationSender;
use Modules\Core\Support\AdminNotificationChannelSettings;
use Tests\TestCase;

final class AdminNotificationChannelsTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        $this->originalConnection = (string) config('database.default');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('value_type');
            $table->text('value');
            $table->string('group');
            $table->json('description')->nullable();
            $table->json('enum_options')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->originalConnection);

        parent::tearDown();
    }

    public function test_it_encrypts_channel_configuration_and_preserves_an_unchanged_token(): void
    {
        $settings = new AdminNotificationChannelSettings;
        $channelId = '61fd1eef-262a-44b4-97a4-7aab6d7402d4';

        $settings->save([$this->channel($channelId)]);

        $stored = (string) DB::table('app_settings')
            ->where('key', AdminNotificationChannelSettings::SETTING_KEY)
            ->value('value');

        $this->assertStringNotContainsString('123456:secret-token', $stored);
        $this->assertStringNotContainsString('-1003816518163', $stored);
        $this->assertSame('123456:secret-token', $settings->all()[0]['bot_token']);

        $channel = $this->channel($channelId);
        $channel['bot_token'] = '';
        $channel['name'] = 'Renamed group';
        $settings->save([$channel]);

        $this->assertSame('123456:secret-token', $settings->all()[0]['bot_token']);
        $this->assertSame('Renamed group', $settings->all()[0]['name']);
    }

    public function test_the_consumer_queues_one_delivery_per_matching_channel(): void
    {
        Queue::fake();
        $settings = new AdminNotificationChannelSettings;
        $channelId = '61fd1eef-262a-44b4-97a4-7aab6d7402d4';
        $settings->save([$this->channel($channelId)]);

        app()->call(
            [new DispatchAdminNotification($settings), 'handleRaw'],
            [
                'payload' => [
                    'payload' => [
                        'event_key' => AdminNotificationChannelSettings::AI_WORKER_REQUEST_EVENT,
                        'event_id' => 'service-request:551',
                        'message' => 'Call the worker',
                    ],
                ],
                'routingKey' => 'admin.notification.requested',
            ],
        );

        Queue::assertPushed(
            SendTelegramAdminNotification::class,
            fn (SendTelegramAdminNotification $job): bool => $job->channelId === $channelId
                && $job->eventId === 'service-request:551'
                && $job->message === 'Call the worker',
        );
    }

    public function test_existing_service_channels_receive_inactive_worker_chat_alerts(): void
    {
        Queue::fake();
        $settings = new AdminNotificationChannelSettings;
        $channelId = '61fd1eef-262a-44b4-97a4-7aab6d7402d4';
        $settings->save([$this->channel($channelId)]);

        app()->call(
            [new DispatchAdminNotification($settings), 'handleRaw'],
            [
                'payload' => [
                    'payload' => [
                        'event_key' => AdminNotificationChannelSettings::AI_WORKER_CHAT_MESSAGE_EVENT,
                        'event_id' => 'service-chat-message:777:701',
                        'message' => 'A service worker received a chat message',
                    ],
                ],
            ],
        );

        Queue::assertPushed(
            SendTelegramAdminNotification::class,
            fn (SendTelegramAdminNotification $job): bool => $job->channelId === $channelId
                && $job->eventKey === AdminNotificationChannelSettings::AI_WORKER_CHAT_MESSAGE_EVENT
                && $job->eventId === 'service-chat-message:777:701',
        );
    }

    public function test_the_delivery_job_loads_the_secret_at_runtime_and_sends_to_a_topic(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
        $settings = new AdminNotificationChannelSettings;
        $channelId = '61fd1eef-262a-44b4-97a4-7aab6d7402d4';
        $settings->save([$this->channel($channelId)]);

        $job = new SendTelegramAdminNotification(
            channelId: $channelId,
            eventKey: AdminNotificationChannelSettings::AI_WORKER_REQUEST_EVENT,
            eventId: 'service-request:551',
            message: 'Call the worker',
        );
        $job->handle($settings, new TelegramAdminNotificationSender);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.telegram.org/bot123456:secret-token/sendMessage'
            && $request['chat_id'] === '-1003816518163'
            && $request['message_thread_id'] === 91
            && $request['text'] === 'Call the worker');

        $this->assertSame([60, 300, 900, 1800, 3600, 7200, 14400], $job->backoff());
        $this->assertSame(8, $job->tries);
    }

    /** @return array<string, mixed> */
    private function channel(string $id): array
    {
        return [
            'id' => $id,
            'name' => 'Service operations',
            'event_keys' => [AdminNotificationChannelSettings::AI_WORKER_REQUEST_EVENT],
            'bot_token' => '123456:secret-token',
            'chat_id' => '-1003816518163',
            'thread_id' => 91,
            'enabled' => true,
        ];
    }
}
