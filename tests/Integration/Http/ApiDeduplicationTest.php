<?php

namespace Nexus\RefManager\Tests\Integration\Http;

use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Tests\Support\CustomDocument;
use Nexus\RefManager\Tests\TestCase;

class ApiDeduplicationTest extends TestCase
{
    public function testItDefaultsScanYearAndPaginatesPairs(): void
    {
        $year = now()->year;

        Document::create(['title' => 'AI Screening Study', 'year' => $year]);
        Document::create(['title' => 'AI Screening Study', 'year' => $year]);
        Document::create(['title' => 'AI Screening Study', 'year' => $year]);

        $response = $this->getJson('/api/refmanager/duplicates?per_page=1');

        $response->assertOk();
        $response->assertJsonPath('data.year', $year);
        $response->assertJsonPath('data.count', 1);
        $response->assertJsonPath('data.pagination.page', 1);
        $response->assertJsonPath('data.pagination.per_page', 1);
        $response->assertJsonPath('data.pagination.has_more', true);
    }

    public function testPostScanDefaultsYearWhenOmitted(): void
    {
        $year = now()->year;

        Document::create(['title' => 'Scan API Title', 'year' => $year]);
        Document::create(['title' => 'Scan API Title', 'year' => $year]);
        Document::create(['title' => 'Different Year Title', 'year' => $year - 1]);

        $response = $this->postJson('/api/refmanager/deduplicate/scan', [
            'per_page' => 5,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.year', $year);
        $response->assertJsonPath('data.count', 1);
        $response->assertJsonPath('data.pagination.page', 1);
        $response->assertJsonPath('data.pagination.per_page', 5);
    }

    public function testItHonorsScanLimitSafeguard(): void
    {
        $year = now()->year;

        config()->set('refmanager.deduplication.scan_limit', 2);

        Document::create(['title' => 'Same Title', 'year' => $year]);
        Document::create(['title' => 'Same Title', 'year' => $year]);
        Document::create(['title' => 'Same Title', 'year' => $year]);

        $response = $this->getJson('/api/refmanager/duplicates');

        $response->assertOk();
        $response->assertJsonPath('data.scan_limit', 2);
        $response->assertJsonPath('data.scan_limit_reached', true);
        $response->assertJsonPath('data.scanned_documents', 2);
    }

    public function testItUsesConfiguredDocumentModelStatusOnMergeResolution(): void
    {
        config()->set('refmanager.document_model', CustomDocument::class);

        $primary = CustomDocument::create(['title' => 'Primary', 'year' => 2024]);
        $candidate = CustomDocument::create(['title' => 'Candidate', 'year' => 2024]);

        $response = $this->postJson('/api/refmanager/duplicates/resolve', [
            'action' => 'merge',
            'primary_id' => $primary->id,
            'candidate_ids' => [$candidate->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('documents', [
            'id' => $candidate->id,
            'merged_into_id' => $primary->id,
            'status' => CustomDocument::STATUS_EXCLUDED,
            'exclusion_reason' => 'duplicate_merged',
        ]);
    }
}

