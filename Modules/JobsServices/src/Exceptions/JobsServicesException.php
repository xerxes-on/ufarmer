<?php

declare(strict_types=1);

namespace Modules\JobsServices\Exceptions;

use RuntimeException;
use Throwable;

class JobsServicesException extends RuntimeException
{
    public function __construct(
        public readonly string $translationKey,
        public readonly int $statusCode = 400,
        public readonly array $context = [],
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?? $translationKey, 0, $previous);
    }
}
