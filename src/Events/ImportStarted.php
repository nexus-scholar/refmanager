<?php

namespace Nexus\RefManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $format,
        public ?string $filename,
        public array $options
    ) {}
}
