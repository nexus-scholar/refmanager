<?php

namespace Nexus\RefManager\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Nexus\RefManager\Models\ImportResult;

class ImportCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ImportResult $result
    ) {}
}
