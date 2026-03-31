<?php

namespace Nexus\RefManager\Exceptions;

use RuntimeException;

class ParseException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $format,
        public readonly ?string $rawRecord = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
