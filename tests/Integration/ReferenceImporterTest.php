<?php

namespace Nexus\RefManager\Tests\Integration;

use Illuminate\Support\Facades\Event;
use Nexus\RefManager\Events\ImportCompleted;
use Nexus\RefManager\Models\Document;
use Nexus\RefManager\ReferenceImporter;
use Nexus\RefManager\Tests\TestCase;

class ReferenceImporterTest extends TestCase
{
    private ReferenceImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = app(ReferenceImporter::class);
    }

    public function test_it_imports_ris_file_and_hydrates_documents(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.ris');

        $this->assertTrue($result->wasSuccessful());
        $this->assertNotEmpty($result->imported);
        $this->assertInstanceOf(Document::class, $result->imported->first());
    }

    public function test_it_detects_duplicate_by_doi(): void
    {
        $existingDoc = Document::create([
            'title' => 'Existing Paper',
            'doi' => '10.1016/j.compag.2024.001',
            'year' => 2024,
        ]);

        $ris = "TY  - JOUR\nTI  - Test Paper\nDO  - 10.1016/j.compag.2024.001\nPY  - 2024\nER  -\n";

        $result = $this->importer->fromString($ris, 'ris');

        $this->assertNotEmpty($result->duplicates);
    }

    public function test_it_saves_documents_when_save_option_true(): void
    {
        $result = $this->importer->withOptions(['save' => true, 'deduplicate' => false])
            ->fromFile(__DIR__.'/../fixtures/sample.ris');

        $this->assertGreaterThan(0, $result->count());
    }

    public function test_it_imports_bibtex_file(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.bib');

        $this->assertTrue($result->wasSuccessful());
        $this->assertCount(3, $result->documents);
    }

    public function test_it_imports_csl_json_file(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.json');

        $this->assertTrue($result->wasSuccessful());
        $this->assertCount(2, $result->documents);
    }

    public function test_it_imports_xml_file(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.xml');

        $this->assertTrue($result->wasSuccessful());
        $this->assertCount(1, $result->documents);
    }

    public function test_it_fires_import_completed_event(): void
    {
        Event::fake([ImportCompleted::class]);

        $this->importer->fromFile(__DIR__.'/../fixtures/sample.ris');

        Event::assertDispatched(ImportCompleted::class);
    }

    public function test_it_returns_zero_failed_on_valid_input(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.ris');
        $this->assertTrue($result->failed->isEmpty());
    }

    public function test_it_handles_string_import_with_format_name(): void
    {
        $ris = "TY  - JOUR\nTI  - Test\nPY  - 2024\nER  -\n";
        $result = $this->importer->fromString($ris, 'ris');

        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(1, $result->total());
    }

    public function test_it_resolves_new_importer_instances_from_container(): void
    {
        $first = app(ReferenceImporter::class);
        $second = app(ReferenceImporter::class);

        $this->assertNotSame($first, $second);
    }

    public function test_it_maps_chapter_container_title_to_book_title(): void
    {
        $csl = json_encode([
            [
                'type' => 'chapter',
                'title' => 'A Chapter Title',
                'container-title' => 'Handbook of Evidence Synthesis',
                'issued' => ['date-parts' => [[2024]]],
            ],
        ]);

        $result = $this->importer->fromString((string) $csl, 'csl_json');
        $document = $result->imported->first();

        $this->assertInstanceOf(Document::class, $document);
        $this->assertSame('Handbook of Evidence Synthesis', $document->book_title);
        $this->assertNull($document->journal);
    }
}
