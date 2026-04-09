<?php

namespace Nexus\RefManager\Tests\Unit\Models;

use Nexus\RefManager\Models\Author;
use PHPUnit\Framework\TestCase;

class AuthorTest extends TestCase
{
    public function testGetFullNameWithGivenAndFamilyName(): void
    {
        $author = new Author([
            'given_name' => 'Ada',
            'family_name' => 'Lovelace',
        ]);

        $this->assertSame('Ada Lovelace', $author->getFullName());
    }

    public function testGetFullNameWithFamilyNameOnly(): void
    {
        $author = new Author([
            'family_name' => 'Plato',
        ]);

        $this->assertSame('Plato', $author->getFullName());
    }
}

