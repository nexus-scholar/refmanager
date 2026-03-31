<?php

namespace Nexus\RefManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexus\RefManager\ReferenceExporter
 */
class RefExporter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nexus\RefManager\ReferenceExporter::class;
    }
}
