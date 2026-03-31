# Testing Guide

## Test Setup (Orchestra Testbench)

```php
// tests/TestCase.php
namespace Shabakah\RefManager\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Shabakah\RefManager\RefManagerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [RefManagerServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
```

---

## Unit Tests — Format Parsers

### RisFormat Test

```php
// tests/Unit/Formats/RisFormatTest.php
class RisFormatTest extends TestCase
{
    private RisFormat $format;

    protected function setUp(): void
    {
        parent::setUp();
        $this->format = new RisFormat();
    }

    /** @test */
    public function it_parses_a_single_ris_record(): void
    {
        $ris = <<<RIS
        TY  - JOUR
        TI  - Plant Disease Detection with CNN
        AU  - Smith, John
        AU  - Doe, Jane
        AB  - Abstract text here.
        DO  - 10.1000/test.001
        PY  - 2024
        JO  - Test Journal
        VL  - 5
        IS  - 2
        SP  - 100
        EP  - 115
        KW  - CNN
        KW  - plant disease
        ER  -
        RIS;

        $records = $this->format->parse($ris);

        expect($records)->toHaveCount(1);
        $r = $records->first();
        expect($r['title'])->toBe('Plant Disease Detection with CNN');
        expect($r['author'])->toHaveCount(2);
        expect($r['author'][0]['family'])->toBe('Smith');
        expect($r['DOI'])->toBe('10.1000/test.001');
        expect($r['page'])->toBe('100-115');
        expect($r['keyword'])->toBe(['CNN', 'plant disease']);
        expect($r['issued']['date-parts'][0][0])->toBe(2024);
    }

    /** @test */
    public function it_parses_multiple_records(): void
    {
        $ris = file_get_contents(__DIR__.'/../../fixtures/multiple_records.ris');
        expect($this->format->parse($ris))->toHaveCount(5);
    }

    /** @test */
    public function it_handles_abstract_continuation_lines(): void
    {
        $ris = "TY  - JOUR\nTI  - Title\nAB  - First line\n  second line\nER  -\n";
        $r = $this->format->parse($ris)->first();
        expect($r['abstract'])->toContain('First line');
        expect($r['abstract'])->toContain('second line');
    }

    /** @test */
    public function it_exports_back_to_valid_ris(): void
    {
        $canonicals = collect([[
            'type'           => 'article-journal',
            'title'          => 'Test Title',
            'author'         => [['family' => 'Smith', 'given' => 'John']],
            'DOI'            => '10.1000/xyz',
            'issued'         => ['date-parts' => [[2024]]],
            'container-title'=> 'Test Journal',
            'keyword'        => ['ai', 'ml'],
        ]]);

        $output = $this->format->serialize($canonicals);

        expect($output)->toContain('TY  - JOUR');
        expect($output)->toContain('TI  - Test Title');
        expect($output)->toContain('AU  - Smith, John');
        expect($output)->toContain('DO  - 10.1000/xyz');
        expect($output)->toContain('ER  -');
    }
}
```

### BibTexFormat Test

```php
/** @test */
public function it_parses_bibtex_with_and_separated_authors(): void
{
    $bib = '@article{smith2024, title={Test}, author={Smith, John and Doe, Jane}, year={2024}}';
    $records = (new BibTexFormat())->parse($bib);
    expect($records->first()['author'])->toHaveCount(2);
}

/** @test */
public function it_normalizes_double_dash_pages(): void
{
    $bib = '@article{x, title={T}, pages={100--115}, year={2024}}';
    $r = (new BibTexFormat())->parse($bib)->first();
    expect($r['page'])->toBe('100-115');
}
```

---

## Integration Tests — Importer

```php
// tests/Integration/ReferenceImporterTest.php
class ReferenceImporterTest extends TestCase
{
    /** @test */
    public function it_imports_ris_file_and_hydrates_documents(): void
    {
        $importer = app(ReferenceImporter::class);
        $result   = $importer->fromFile(__DIR__.'/../fixtures/sample.ris');

        expect($result->wasSuccessful())->toBeTrue();
        expect($result->imported)->not->toBeEmpty();
        expect($result->imported->first())->toBeInstanceOf(Document::class);
    }

    /** @test */
    public function it_detects_duplicate_by_doi(): void
    {
        Document::factory()->create(['doi' => '10.1000/test.001']);

        $ris    = "TY  - JOUR\nTI  - Any Title\nDO  - 10.1000/test.001\nPY  - 2024\nER  -\n";
        $result = app(ReferenceImporter::class)->fromString($ris, 'ris');

        expect($result->duplicates)->toHaveCount(1);
        expect($result->imported)->toBeEmpty();
    }

    /** @test */
    public function it_saves_documents_when_save_option_true(): void
    {
        $result = app(ReferenceImporter::class)
            ->withOptions(['save' => true, 'deduplicate' => false])
            ->fromFile(__DIR__.'/../fixtures/sample.ris');

        $this->assertDatabaseCount('documents', $result->count());
    }

    /** @test */
    public function it_fires_import_completed_event(): void
    {
        Event::fake([ImportCompleted::class]);

        app(ReferenceImporter::class)->fromFile(__DIR__.'/../fixtures/sample.ris');

        Event::assertDispatched(ImportCompleted::class);
    }
}
```

---

## Round-Trip Tests (Import → Export → Re-import)

```php
/** @test */
public function ris_round_trip_preserves_core_fields(): void
{
    $original = collect([[
        'type'            => 'article-journal',
        'title'           => 'Round Trip Test',
        'DOI'             => '10.9999/roundtrip',
        'author'          => [['family' => 'García', 'given' => 'María']],
        'issued'          => ['date-parts' => [[2024, 6, 1]]],
        'container-title' => 'Journal of Testing',
        'keyword'         => ['round-trip', 'unicode'],
    ]]);

    $format     = new RisFormat();
    $exported   = $format->serialize($original);
    $reimported = $format->parse($exported);

    $r = $reimported->first();
    expect($r['title'])->toBe('Round Trip Test');
    expect($r['DOI'])->toBe('10.9999/roundtrip');
    expect($r['author'][0]['family'])->toBe('García');
    expect($r['keyword'])->toContain('unicode');
}

/** @test */
public function bibtex_round_trip_preserves_core_fields(): void
// ... same pattern for BibTeX
```

---

## Test Fixtures

Place in `tests/fixtures/`:

| File | Contents |
|------|----------|
| `sample.ris` | 3 journal articles with full fields |
| `multiple_records.ris` | 5 mixed-type records |
| `sample.bib` | 3 BibTeX entries including `@inproceedings` |
| `sample.json` | Valid CSL-JSON array with 3 items |
| `sample.xml` | EndNote XML with 2 records |
| `duplicates.ris` | 2 records sharing DOI with `sample.ris` |
| `unicode.ris` | Arabic/French/Chinese author names |
| `malformed.ris` | Missing `ER  -` terminator (error handling test) |

