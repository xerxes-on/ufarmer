<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use RuntimeException;
use Throwable;

class CoreException extends RuntimeException
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
