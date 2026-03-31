<?php

namespace Nexus\RefManager\Exceptions;

use RuntimeException;

class UnsupportedFormatException extends RuntimeException
{
    public function __construct(string $format)
    {
        parent::__construct("Unsupported reference format: [{$format}].");
    }
}
