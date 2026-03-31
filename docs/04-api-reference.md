# API Reference

## FormatManager

The central registry. Resolves a `ReferenceFormat` instance from file extension, MIME type, or explicit name.

```php
use Shabakah\RefManager\FormatManager;

$manager = app(FormatManager::class);

// Resolve by extension
$format = $manager->byExtension('ris');      // → RisFormat
$format = $manager->byExtension('bib');      // → BibTexFormat
$format = $manager->byExtension('json');     // → CslJsonFormat
$format = $manager->byExtension('xml');      // → EndNoteXmlFormat

// Resolve by MIME type
$format = $manager->byMime('application/x-research-info-systems');

// Resolve from an UploadedFile automatically
$format = $manager->fromUpload($request->file('library'));

// Register a custom format
$manager->register('marc', MarcFormat::class);

// List all registered formats
$formats = $manager->all(); // ['ris' => RisFormat::class, ...]
```

---

## ReferenceImporter

```php
use Shabakah\RefManager\ReferenceImporter;

$importer = app(ReferenceImporter::class);

// Import from file path — format auto-detected from extension
$result = $importer->fromFile('/path/to/library.ris');

// Import from UploadedFile (Laravel HTTP)
$result = $importer->fromUpload($request->file('library'));

// Import from raw string with explicit format
$result = $importer->fromString($risContent, 'ris');

// With options
$result = $importer->withOptions([
    'deduplicate'   => true,          // default: true
    'save'          => false,         // default: false (return hydrated, unsaved models)
    'collection_id' => 42,            // auto-attach imported docs to a collection
    'project_id'    => 7,
])->fromFile('/path/to/library.bib');
```

### ImportResult Value Object

```php
$result->documents;     // Collection<Document>  — all parsed (includes duplicates)
$result->imported;      // Collection<Document>  — net new documents
$result->duplicates;    // Collection<DuplicateResult> — skipped with reason
$result->failed;        // Collection<array>     — ['record' => ..., 'error' => ...]
$result->log;           // ImportLog model       — persisted audit record
$result->total;         // int
$result->count();       // int (imported count)
$result->wasSuccessful(); // bool (failed === 0)
```

---

## ReferenceExporter

```php
use Shabakah\RefManager\ReferenceExporter;

$exporter = app(ReferenceExporter::class);

// Export a document collection to a string
$risString  = $exporter->toString(Document::all(), 'ris');
$bibString  = $exporter->toString($documents, 'bibtex');
$jsonString = $exporter->toString($documents, 'csl_json');

// Export as a streaming HTTP response (memory-safe for large collections)
return $exporter->toResponse($documents, 'ris', 'my-library.ris');

// Export only screened-in documents (SLR use-case)
$included = Document::whereHas('screening', fn($q) => $q->where('decision', 'included'))
                    ->with('authors')
                    ->get();
return $exporter->toResponse($included, 'bibtex', 'included-references.bib');

// Export a named collection
$collection = ReferenceCollection::find(1);
return $exporter->fromCollection($collection, 'csl_json');
```

---

## ReferenceCollection

```php
use Shabakah\RefManager\Models\ReferenceCollection;

// Create
$col = ReferenceCollection::create([
    'name'       => 'Plant Disease Papers 2024',
    'project_id' => 7,
]);

// Add documents
$col->documents()->attach($documentIds);
$col->documents()->attach($doc->id, ['note' => 'Key reference']);

// Remove
$col->documents()->detach($documentId);

// Export the collection
app(ReferenceExporter::class)->fromCollection($col, 'ris');

// Query
$col->documents()->with('authors')->paginate(20);
```

---

## ReferenceFormat Interface

Implement this to add a custom format:

```php
namespace Shabakah\RefManager\Formats\Contracts;

use Illuminate\Support\Collection;

interface ReferenceFormat
{
    /**
     * Parse raw file content into a Collection of canonical arrays.
     * Each array follows the CSL-JSON schema.
     *
     * @throws \Shabakah\RefManager\Exceptions\ParseException
     */
    public function parse(string $content): Collection;

    /**
     * Serialize a Collection of canonical arrays to the format's raw string.
     */
    public function serialize(Collection $canonicals): string;

    /**
     * File extensions handled by this format (lowercase, no dot).
     * @return string[]
     */
    public function extensions(): array;

    /**
     * MIME types for this format.
     * @return string[]
     */
    public function mimeTypes(): array;

    /**
     * Human-readable name.
     */
    public function label(): string;
}
```

---

## Artisan Commands

```bash
# Import a file into a project's document library
php artisan refmanager:import {file} {--project=} {--collection=} {--format=} {--dry-run}

# Export a project's documents
php artisan refmanager:export {--project=} {--collection=} {--format=ris} {--output=}

# Detect and list duplicates (without merging)
php artisan refmanager:duplicates {--project=} {--fix}

# Show supported formats
php artisan refmanager:formats
```

### Example Usage
```bash
# Dry-run import to preview what would be added
php artisan refmanager:import storage/library.ris --project=7 --dry-run

# Export project 7 to BibTeX
php artisan refmanager:export --project=7 --format=bibtex --output=storage/exports/p7.bib

# Find and auto-merge duplicates by DOI in project 7
php artisan refmanager:duplicates --project=7 --fix
```

---

## Events

| Event | Payload | When |
|-------|---------|------|
| `ImportStarted` | `format`, `filename`, `options` | Before parsing begins |
| `ImportCompleted` | `ImportResult` | After all records processed |
| `ExportStarted` | `format`, `count` | Before serialization |
| `ExportCompleted` | `format`, `count`, `bytes` | After serialization |
| `DuplicateDetected` | `DuplicateResult` | Each duplicate found during import |

```php
// Listening to events in your app's EventServiceProvider
Event::listen(ImportCompleted::class, function ($event) {
    Log::info("Import done: {$event->result->count()} new documents");
});
```

