<?php

declare(strict_types=1);

namespace App\Events;

use Xerxes\RabbitMQ\Support\ShouldPublish;

class RawNotificationEvent implements ShouldPublish
{
    public string $exchange = 'ufarm.events';

    /**
     * @param  int|array<int>  $authIds  Single auth_id or array of auth_ids to notify
     * @param  string  $type  Notification type identifier
     * @param  string|array<string, string>  $title  Notification title (string or locale-keyed array)
     * @param  string|array<string, string>  $body  Notification body (string or locale-keyed array)
     * @param  array  $data  Additional data payload
     */
    public function __construct(
        public readonly int|array $authIds,
        public readonly string $type,
        public readonly string|array $title,
        public readonly string|array $body,
        public readonly array $data = [],
    ) {}

    public function routingKey(): string
    {
        return 'ufarm_notifications.raw';
    }
}
