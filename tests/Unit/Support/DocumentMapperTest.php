<?php

namespace Nexus\RefManager\Tests\Unit\Support;

use Nexus\RefManager\Support\DocumentMapper;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DocumentMapperTest extends TestCase
{
    public function testItThrowsWhenNexusPhpIsNotInstalled(): void
    {
        if (class_exists('\\Nexus\\Models\\Document')) {
            $this->markTestSkipped('nexus/nexus-php is installed in this environment.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nexus/nexus-php is required');

        DocumentMapper::fromNexus(new \stdClass());
    }
}

