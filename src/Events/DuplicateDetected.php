<?php

namespace Nexus\RefManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nexus\RefManager\Models\DuplicateResult;

class DuplicateDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DuplicateResult $result
    ) {}
}
