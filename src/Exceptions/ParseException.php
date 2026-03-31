<?php

namespace Nexus\RefManager\Exceptions;

use RuntimeException;

class ParseException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $format,
        public readonly ?string $rawRecord = null,
        public readonly ?int $recordIndex = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getMeta(): array
    {
        return [
            'format' => $this->format,
            'record_index' => $this->recordIndex,
            'raw_record_preview' => $this->rawRecord !== null
                ? mb_substr($this->rawRecord, 0, 200)
                : null,
        ];
    }
}
