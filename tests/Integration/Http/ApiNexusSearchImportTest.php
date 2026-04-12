<?php

namespace Nexus\RefManager\Tests\Integration\Http;

use Nexus\RefManager\Http\Controllers\NexusSearchImportController;
use Nexus\RefManager\Services\AuthorResolver;
use Nexus\RefManager\Services\DuplicateDetector;
use Nexus\RefManager\Tests\Support\CustomDocument;
use Nexus\RefManager\Tests\TestCase;
use ReflectionMethod;

class ApiNexusSearchImportTest extends TestCase
{
    public function test_it_returns_validation_message_when_nexus_is_unavailable_or_unbound(): void
    {
        $response = $this->postJson('/api/refmanager/nexus/search-import', [
            'query' => 'machine learning in agriculture',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    public function test_to_canonical_sets_issued_to_null_when_year_is_missing(): void
    {
        $controller = new NexusSearchImportController(
            app(DuplicateDetector::class),
            app(AuthorResolver::class),
        );

        $method = new ReflectionMethod($controller, 'toCanonical');

        $result = (object) [
            'title' => 'No Year Result',
            'year' => null,
            'externalIds' => null,
            'authors' => [],
        ];

        $canonical = $method->invoke($controller, $result);

        $this->assertArrayHasKey('issued', $canonical);
        $this->assertNull($canonical['issued']);
    }

    public function test_resolve_imported_status_uses_configured_model_constant_when_available(): void
    {
        $controller = new NexusSearchImportController(
            app(DuplicateDetector::class),
            app(AuthorResolver::class),
        );

        $method = new ReflectionMethod($controller, 'resolveImportedStatus');

        $status = $method->invoke($controller, CustomDocument::class);

        $this->assertSame(CustomDocument::STATUS_IMPORTED, $status);
    }
}
