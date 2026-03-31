<?php

namespace Nexus\RefManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $format,
        public int $count,
        public int $bytes
    ) {}
}
