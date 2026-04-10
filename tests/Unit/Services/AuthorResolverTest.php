<?php

namespace Nexus\RefManager\Tests\Unit\Services;

use Nexus\RefManager\Models\Author;
use Nexus\RefManager\Services\AuthorResolver;
use Nexus\RefManager\Tests\TestCase;

class AuthorResolverTest extends TestCase
{
    public function test_resolve_prioritizes_orcid(): void
    {
        $resolver = new AuthorResolver;

        $first = $resolver->resolve([
            'family' => 'Smith',
            'given' => 'John',
            'ORCID' => '0000-0002-1825-0097',
        ]);

        $second = $resolver->resolve([
            'family' => 'Smyth',
            'given' => 'J.',
            'ORCID' => '0000-0002-1825-0097',
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Author::where('orcid', '0000-0002-1825-0097')->count());
    }

    public function test_resolve_falls_back_to_name_without_orcid(): void
    {
        $resolver = new AuthorResolver;

        $first = $resolver->resolve([
            'family' => 'Lovelace',
            'given' => 'Ada',
        ]);

        $second = $resolver->resolve([
            'family' => 'Lovelace',
            'given' => 'Ada',
        ]);

        $this->assertSame($first->id, $second->id);
    }
}
