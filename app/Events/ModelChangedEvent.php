<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Xerxes\RabbitMQ\Support\ShouldPublish;

/**
 * Mirrors ufarm-api's Modules\Core\Events\ModelChangedEvent — same exchange,
 * same `ufarm.{model}.{action}` routing key, same payload shape — so that
 * crop changes made in this panel reach the exact consumers already bound to
 * ufarm-api's crop events (e.g. the LMS `lms.parent_crops` queue) with no
 * consumer-side change.
 *
 * The exchange name is read from config rather than hardcoded so it stays in
 * step with ufarm-api's RABBITMQ_FARMER_EXCHANGE.
 */
final class ModelChangedEvent implements ShouldPublish
{
    use Dispatchable;

    public string $exchange;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $model,
        public readonly string $action,
        public readonly array $payload = [],
    ) {
        $this->exchange = (string) config('services.farmer_events.exchange', 'ufarm.events');
    }

    public function routingKey(): string
    {
        return "ufarm.{$this->model}.{$this->action}";
    }
}
