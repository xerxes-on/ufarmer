<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\Services;

use InvalidArgumentException;
use Xerxes\BillingClient\Contracts\EntityParserInterface;
use Xerxes\BillingClient\DTOs\ParsedEntityDTO;
use Xerxes\BillingClient\Enums\PaymentValidationError;

class EntityIdParser implements EntityParserInterface
{
    public function parse(string $entityId): ParsedEntityDTO
    {
        $separator = config('ufarm-billing-client.entity_separator', ':');
        $parts = explode($separator, $entityId, 2);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException(
                PaymentValidationError::InvalidEntityFormat->message()
            );
        }

        [$serviceTypeAlias, $id] = $parts;

        if (empty($serviceTypeAlias) || empty($id)) {
            throw new InvalidArgumentException(
                PaymentValidationError::InvalidEntityFormat->message()
            );
        }

        return new ParsedEntityDTO(
            rawEntityId: $entityId,
            serviceTypeAlias: $serviceTypeAlias,
            entityId: $id,
        );
    }

    public function build(string $serviceTypeAlias, int|string $id): string
    {
        $separator = config('ufarm-billing-client.entity_separator', ':');

        return $serviceTypeAlias.$separator.$id;
    }
}
