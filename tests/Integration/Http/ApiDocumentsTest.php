<?php

namespace Nexus\RefManager\Tests\Integration\Http;

use Nexus\RefManager\Models\Author;
use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Tests\TestCase;

class ApiDocumentsTest extends TestCase
{
    public function testItListsDocumentsWithFilters(): void
    {
        $included = Document::create([
            'title' => 'Systematic Review of Autonomous Farming',
            'year' => 2024,
            'status' => Document::STATUS_INCLUDED,
        ]);

        $excluded = Document::create([
            'title' => 'Unrelated Dataset Paper',
            'year' => 2023,
            'status' => Document::STATUS_EXCLUDED,
        ]);

        $author = Author::create([
            'given_name' => 'John',
            'family_name' => 'Smith',
        ]);

        $included->authors()->attach($author->id, ['author_order' => 1]);

        $response = $this->getJson('/api/refmanager/documents?status=included&year=2024&q=autonomous');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $included->id);
        $response->assertJsonPath('data.0.authors.0.full_name', 'John Smith');

        $this->assertDatabaseHas('documents', ['id' => $excluded->id]);
    }

    public function testItUpdatesAndDeletesDocumentThroughApi(): void
    {
        $document = Document::create([
            'title' => 'Initial Title',
            'status' => Document::STATUS_IMPORTED,
            'year' => 2022,
        ]);

        $updateResponse = $this->patchJson('/api/refmanager/documents/'.$document->id, [
            'status' => Document::STATUS_INCLUDED,
            'title' => 'Updated Title',
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJsonPath('data.status', Document::STATUS_INCLUDED);
        $updateResponse->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => Document::STATUS_INCLUDED,
            'title' => 'Updated Title',
        ]);

        $deleteResponse = $this->deleteJson('/api/refmanager/documents/'.$document->id);
        $deleteResponse->assertNoContent();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }
}

