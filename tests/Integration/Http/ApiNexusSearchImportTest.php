<?php

namespace Nexus\RefManager\Tests\Integration\Http;

use Nexus\RefManager\Tests\TestCase;

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
}
