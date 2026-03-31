# Data Model & Integration

## Existing Models Used

The package **extends** the existing Shabakah models rather than replacing them.

```
Document (existing)
  ├── authors(): BelongsToMany(Author)  [pivot: document_author, author_order]
  ├── searchProvenance(): HasMany(SearchResult)
  ├── screening(): HasOne(Screening)
  └── clusters(): BelongsToMany(Cluster)

Author (existing)
  └── documents(): BelongsToMany(Document)
```

---

## New Tables Added by This Package

### `reference_collections`
Named library / reference list (like a Zotero collection or Mendeley group).

```sql
CREATE TABLE reference_collections (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id    BIGINT UNSIGNED NULL,          -- FK to projects (nullable = personal library)
    name          VARCHAR(255) NOT NULL,
    description   TEXT NULL,
    meta          JSON NULL,                      -- arbitrary extra fields
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
```

### `reference_collection_document` (pivot)
```sql
CREATE TABLE reference_collection_document (
    collection_id  BIGINT UNSIGNED NOT NULL,
    document_id    BIGINT UNSIGNED NOT NULL,
    added_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note           TEXT NULL,
    PRIMARY KEY (collection_id, document_id),
    FOREIGN KEY (collection_id) REFERENCES reference_collections(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id)   REFERENCES documents(id) ON DELETE CASCADE
);
```

### `import_logs`
Audit trail for every import operation.

```sql
CREATE TABLE import_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    format          VARCHAR(50) NOT NULL,          -- 'ris' | 'bibtex' | 'csl_json' | 'endnote_xml'
    filename        VARCHAR(255) NULL,
    total_parsed    INT UNSIGNED DEFAULT 0,
    imported        INT UNSIGNED DEFAULT 0,
    duplicates      INT UNSIGNED DEFAULT 0,
    failed          INT UNSIGNED DEFAULT 0,
    collection_id   BIGINT UNSIGNED NULL,
    meta            JSON NULL,                      -- e.g., error details per record
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);
```

---

## Document Model — New Methods (via Trait)

Add `Shabakah\RefManager\Concerns\HasBibliographicExport` trait to `Document`:

```php
use Shabakah\RefManager\Concerns\HasBibliographicExport;

class Document extends Model {
    use HasBibliographicExport;
    // existing code unchanged
}
```

The trait adds:

```php
// Convert this Document to the canonical CSL-JSON array
public function toCanonical(): array;

// Convert to a specific format string
public function toRis(): string;
public function toBibTex(): string;
public function toCslJson(): string;
public function toEndNoteXml(): string;

// Named collection relationship
public function referenceCollections(): BelongsToMany;
```

---

## Field Mapping: Document → Canonical Array

```php
public function toCanonical(): array
{
    return [
        'type'            => $this->resolveType(),          // from document_type field
        'title'           => $this->title,
        'abstract'        => $this->abstract,
        'DOI'             => $this->doi,
        'URL'             => $this->url,
        'language'        => $this->language,
        'container-title' => $this->journal ?? $this->book_title,
        'volume'          => $this->volume,
        'issue'           => $this->issue,
        'page'            => $this->pages,
        'publisher'       => $this->publisher,
        'publisher-place' => $this->publisher_place,
        'issued'          => $this->formatIssuedDate(),
        'author'          => $this->authors
                                ->sortBy('pivot.author_order')
                                ->map(fn($a) => [
                                    'family' => $a->last_name,
                                    'given'  => $a->first_name,
                                    'ORCID'  => $a->orcid ?? null,
                                ])->values()->toArray(),
        'keyword'         => $this->keywords ?? [],
    ];
}
```

---

## Author Model — Parsing Helpers

`AuthorResolver` handles the three name formats found in the wild:

| Input String | Strategy | Output |
|---|---|---|
| `Smith, John A.` | comma split | `family=Smith, given=John A.` |
| `John A. Smith` | last-word heuristic | `family=Smith, given=John A.` |
| `Smith J` | initial detection | `family=Smith, given=J` |
| `{IEEE Task Force}` | literal/organization | `literal=IEEE Task Force` |

```php
// AuthorResolver::parse(string $raw): array
// Returns: ['family' => ..., 'given' => ..., 'literal' => ...]
```

DB upsert uses `firstOrCreate` on `['last_name', 'first_name']` pair with optional ORCID enrichment.

---

## Migration Files (Execution Order)

```
2024_01_01_000001_create_reference_collections_table.php
2024_01_01_000002_create_reference_collection_document_table.php
2024_01_01_000003_create_import_logs_table.php
```

Run with: `php artisan migrate`

The package registers migrations via `$this->loadMigrationsFrom(__DIR__.'/../database/migrations')` in the service provider.

