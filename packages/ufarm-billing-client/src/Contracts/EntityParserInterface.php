<?php

declare(strict_types=1);

namespace Xerxes\BillingClient\Contracts;

use InvalidArgumentException;
use Xerxes\BillingClient\DTOs\ParsedEntityDTO;

interface EntityParserInterface
{
    /**
     * Parse entity ID into components.
     *
     * @param  string  $entityId  Format: "{service_type}:{id}"
     *
     * @throws InvalidArgumentException If format is invalid
     */
    public function parse(string $entityId): ParsedEntityDTO;

    /**
     * Build entity ID from components.
     */
    public function build(string $serviceTypeAlias, int|string $id): string;
}
