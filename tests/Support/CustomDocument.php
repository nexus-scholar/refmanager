<?php

namespace Nexus\RefManager\Tests\Support;

use Nexus\RefManager\Models\Document;

class CustomDocument extends Document
{
    protected $table = 'documents';

    public const STATUS_IMPORTED = 'custom_imported';

    public const STATUS_EXCLUDED = 'custom_excluded';
}

