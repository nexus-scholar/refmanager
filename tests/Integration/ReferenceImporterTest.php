<?php

namespace Nexus\RefManager\Tests\Integration;

use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Models\ImportLog;
use Nexus\RefManager\ReferenceImporter;
use Nexus\RefManager\Tests\TestCase;
use Nexus\RefManager\Events\ImportCompleted;
use Illuminate\Support\Facades\Event;

class ReferenceImporterTest extends TestCase
{
    private ReferenceImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = app(ReferenceImporter::class);
    }

    public function testItImportsRisFileAndHydratesDocuments(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.ris');

        $this->assertTrue($result->wasSuccessful());
        $this->assertNotEmpty($result->imported);
        $this->assertInstanceOf(Document::class, $result->imported->first());
    }

    public function testItDetectsDuplicateByDoi(): void
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

    public function testItSavesDocumentsWhenSaveOptionTrue(): void
    {
        $result = $this->importer->withOptions(['save' => true, 'deduplicate' => false])
            ->fromFile(__DIR__.'/../fixtures/sample.ris');

        $this->assertGreaterThan(0, $result->count());
    }

    public function testItImportsBibtexFile(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.bib');

        $this->assertTrue($result->wasSuccessful());
        $this->assertCount(3, $result->documents);
    }

    public function testItImportsCslJsonFile(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.json');

        $this->assertTrue($result->wasSuccessful());
        $this->assertCount(2, $result->documents);
    }

    public function testItImportsXmlFile(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.xml');

        $this->assertTrue($result->wasSuccessful());
        $this->assertCount(1, $result->documents);
    }

    public function testItFiresImportCompletedEvent(): void
    {
        Event::fake([ImportCompleted::class]);

        $this->importer->fromFile(__DIR__.'/../fixtures/sample.ris');

        Event::assertDispatched(ImportCompleted::class);
    }

    public function testItReturnsZeroFailedOnValidInput(): void
    {
        $result = $this->importer->fromFile(__DIR__.'/../fixtures/sample.ris');
        $this->assertTrue($result->failed->isEmpty());
    }

    public function testItHandlesStringImportWithFormatName(): void
    {
        $ris = "TY  - JOUR\nTI  - Test\nPY  - 2024\nER  -\n";
        $result = $this->importer->fromString($ris, 'ris');

        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(1, $result->total());
    }

    public function testItMapsChapterContainerTitleToBookTitle(): void
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
