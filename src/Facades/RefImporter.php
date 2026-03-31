<?php

namespace Nexus\RefManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexus\RefManager\ReferenceImporter
 */
class RefImporter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nexus\RefManager\ReferenceImporter::class;
    }
}
