# Integration Patterns & Roadmap

## Integrating with Existing Shabakah Models

### Adding the Trait to Document

```php
// In your host app's Document model (or via a migration-free approach)
use Shabakah\RefManager\Concerns\HasBibliographicExport;

class Document extends Model
{
    use HasBibliographicExport;
    // ... existing code unchanged
}
```

The package detects the trait at runtime — if not present, export still works via `ReferenceExporter` directly.

---

### SLR Workflow Integration

The package slots directly into the post-screening export step of the Shabakah SLR pipeline:

```
SearchRun → SearchResult → Document → Screening → INCLUDE/EXCLUDE
                                                        ↓
                                            ReferenceExporter (included only)
                                                        ↓
                                             .ris / .bib / .json
```

```php
// ExportIncludedController.php
public function export(SearchRun $run, Request $request)
{
    $format = $request->input('format', 'ris');

    $documents = Document::whereHas('searchProvenance', fn($q) =>
            $q->where('search_run_id', $run->id)
        )
        ->whereHas('screening', fn($q) =>
            $q->where('decision', 'included')
        )
        ->with('authors')
        ->get();

    return app(ReferenceExporter::class)
        ->toResponse($documents, $format, "slr-{$run->id}-included.{$format}");
}
```

---

### Cluster-Aware Export

Export documents grouped by cluster with BibTeX `groups` field:

```php
$run->clusters->each(function (Cluster $cluster) use ($exporter) {
    $docs = $cluster->members()->with('authors')->get();
    $bib  = $exporter->toString($docs, 'bibtex');
    Storage::put("exports/cluster-{$cluster->id}.bib", $bib);
});
```

---

## Controller Boilerplate

### Import Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Shabakah\RefManager\ReferenceImporter;

class ReferenceImportController extends Controller
{
    public function store(Request $request, ReferenceImporter $importer)
    {
        $request->validate([
            'library'    => 'required|file|max:20480',  // 20MB max
            'project_id' => 'required|exists:projects,id',
        ]);

        $result = $importer
            ->withOptions([
                'deduplicate' => true,
                'save'        => true,
                'project_id'  => $request->project_id,
            ])
            ->fromUpload($request->file('library'));

        return response()->json([
            'imported'   => $result->count(),
            'duplicates' => $result->duplicates->count(),
            'failed'     => $result->failed->count(),
            'log_id'     => $result->log->id,
        ]);
    }
}
```

### Export Controller

```php
class ReferenceExportController extends Controller
{
    public function show(Request $request, ReferenceExporter $exporter)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'format'     => 'required|in:ris,bibtex,csl_json,endnote_xml',
            'filter'     => 'nullable|in:all,included,excluded',
        ]);

        $query = Document::whereHas('searchProvenance', fn($q) =>
            $q->whereHas('searchRun', fn($q2) =>
                $q2->where('project_id', $request->project_id)
            )
        )->with('authors');

        if ($request->filter === 'included') {
            $query->whereHas('screening', fn($q) => $q->where('decision', 'included'));
        }

        $ext = match($request->format) {
            'bibtex'      => 'bib',
            'csl_json'    => 'json',
            'endnote_xml' => 'xml',
            default       => 'ris',
        };

        return $exporter->toResponse(
            $query->get(),
            $request->format,
            "project-{$request->project_id}-references.{$ext}"
        );
    }
}
```

---

## Config File

```php
// config/refmanager.php
return [
    /*
     | The Document model class used by the package.
     | Override if your app uses a custom Document model.
     */
    'document_model' => \App\Models\Document::class,

    /*
     | The Author model class used for author resolution.
     */
    'author_model' => \App\Models\Author::class,

    /*
     | Deduplication settings
     */
    'deduplication' => [
        'enabled'             => true,
        'doi_exact'           => true,
        'title_year_fuzzy'    => true,
        'fuzzy_threshold'     => 0.92,   // 0.0–1.0 similarity required
    ],

    /*
     | Maximum file size for imports (in kilobytes)
     */
    'max_upload_size_kb' => 20480,   // 20MB

    /*
     | Chunk size for streaming exports
     */
    'export_chunk_size' => 500,

    /*
     | Log all import operations to import_logs table
     */
    'log_imports' => true,
];
```

---

## Roadmap

### v1.0 — Core (This Blueprint)
- [x] Architecture design
- [ ] RIS import/export
- [ ] BibTeX import/export
- [ ] CSL-JSON import/export
- [ ] EndNote XML import/export
- [ ] AuthorResolver
- [ ] DuplicateDetector
- [ ] ReferenceImporter + ReferenceExporter
- [ ] FormatManager
- [ ] ReferenceCollection model + CRUD
- [ ] ImportLog model
- [ ] Artisan commands (import, export, duplicates, formats)
- [ ] Events
- [ ] Full test suite with fixtures

### v1.1 — Quality of Life
- [ ] Laravel Nova resource for ReferenceCollection
- [ ] Filament plugin
- [ ] `HasBibliographicExport` trait auto-detection
- [ ] Import progress via Laravel Broadcasting (chunked large files)
- [ ] Duplicate merge UI helper (returns candidate pairs + merge strategy)

### v2.0 — Cloud Sync
- [ ] Zotero Web API sync (OAuth 2.0)
- [ ] Mendeley API sync
- [ ] Two-way delta sync with conflict resolution
- [ ] Webhook support for real-time sync triggers

### v2.1 — Citation Rendering
- [ ] CSL processor integration (`seboettg/citeproc-php`)
- [ ] Generate formatted bibliography strings (APA, IEEE, Vancouver)
- [ ] Blade component `<x-refmanager::citation :id="$docId" style="apa" />`

