<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\DTOs;

final readonly class ParsedEntityDTO
{
    public function __construct(
        public string $rawEntityId,
        public string $serviceTypeAlias,
        public string $entityId,
    ) {}

    public function getNumericId(): int
    {
        return (int) $this->entityId;
    }
}
