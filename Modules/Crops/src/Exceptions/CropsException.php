<?php

declare(strict_types=1);

namespace Modules\Crops\Exceptions;

use RuntimeException;
use Throwable;

class CropsException extends RuntimeException
{
    public function __construct(
        public readonly string $translationKey,
        public readonly int $statusCode = 400,
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?? $translationKey, 0, $previous);
    }
}
