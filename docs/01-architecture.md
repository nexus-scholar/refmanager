# Architecture Deep-Dive

## Layer Map

```
shabakah/refmanager
│
├── FormatManager          Registry + resolver
│   └── resolves ──────► ReferenceFormat (interface)
│                              ├── RisFormat
│                              ├── BibTexFormat
│                              ├── CslJsonFormat
│                              └── EndNoteXmlFormat
│
├── ReferenceImporter      Orchestrates: file → parse → normalize → dedupe → Collection<Document>
│   ├── uses FormatManager
│   ├── uses AuthorResolver
│   └── uses DuplicateDetector
│
├── ReferenceExporter      Orchestrates: Collection<Document> → serialize → string/stream
│   └── uses FormatManager
│
├── Services/
│   ├── AuthorResolver     Name parsing, ORCID lookup stub, DB upsert
│   ├── DuplicateDetector  DOI exact match → title+year Levenshtein fallback
│   └── ReferenceCollection  Named list management (CRUD, tagging)
│
└── Events/
    ├── ImportStarted
    ├── ImportCompleted
    ├── ExportStarted
    └── ExportCompleted
```

---

## Canonical Internal Format

All adapters normalize to and from a **CSL-JSON-compatible PHP array**. This is the package's lingua franca.

```php
[
    'type'             => 'article-journal',   // CSL type string
    'title'            => 'string',
    'abstract'         => 'string|null',
    'DOI'              => 'string|null',
    'URL'              => 'string|null',
    'language'         => 'string|null',        // ISO 639-1
    'source'           => 'string|null',        // provider name
    'container-title'  => 'string|null',        // journal / book title
    'volume'           => 'string|null',
    'issue'            => 'string|null',
    'page'             => 'string|null',        // "123-145"
    'publisher'        => 'string|null',
    'publisher-place'  => 'string|null',
    'issued'           => ['date-parts' => [[2024, 3, 15]]],
    'author'           => [
        ['family' => 'Smith', 'given' => 'John A.', 'ORCID' => null],
    ],
    'keyword'          => ['tag1', 'tag2'],
    '_raw'             => [],                   // format-specific extras (preserved, not mapped)
]
```

This schema maps cleanly to:
- **RIS**: `TI`, `AB`, `DO`, `AU`, `PY`, `JO`, `VL`, `IS`, `SP`/`EP`, `KW`
- **BibTeX**: `title`, `abstract`, `doi`, `author`, `year`, `journal`, `volume`, `number`, `pages`, `keywords`
- **CSL-JSON**: identity mapping (it IS the schema)
- **EndNote XML**: `<titles>`, `<abstract>`, `<electronic-resource-num>`, `<contributors>`, `<dates>`, `<periodical>`

---

## Import Flow (Step-by-Step)

```
1. FormatManager::detect($file)        → resolves ReferenceFormat instance
2. ReferenceFormat::parse($content)    → Collection of canonical arrays
3. AuthorResolver::resolve($authorList) → Collection<Author> (upserted)
4. DuplicateDetector::check($canonical) → null | existing Document
5. Document::hydrate($canonical)        → Document (not saved)
6. Event: ImportCompleted fired
7. Return Collection<Document>          ← caller saves, discards, or merges
```

## Export Flow (Step-by-Step)

```
1. ReferenceExporter::fromDocuments($docs, $format)
2. Each Document → toCanonical()        → canonical array
3. ReferenceFormat::serialize($collection) → string
4. Event: ExportCompleted fired
5. Return string | StreamedResponse
```

---

## Deduplication Strategy

| Pass | Method | Field |
|------|--------|-------|
| 1 (exact) | String equality, normalized | DOI (lowercased, stripped) |
| 2 (fuzzy) | Levenshtein distance ≤ 5% of string length | title + year combined |
| 3 (manual) | Returns both as candidates | caller resolves |

`DuplicateDetector` returns a `DuplicateResult` value object:
```php
class DuplicateResult {
    public bool $isDuplicate;
    public ?Document $existing;   // null when no duplicate found
    public float $confidence;     // 0.0–1.0
    public string $matchedBy;     // 'doi' | 'title_year' | 'none'
}
```

