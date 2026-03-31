# Implementation Guide

## Step-by-Step Build Order

Follow this order strictly — each step depends on the previous.

```
Step 1  → Service Provider + composer.json
Step 2  → ReferenceFormat interface + exceptions
Step 3  → CslJsonFormat  (simplest — identity mapping, validates internal schema)
Step 4  → RisFormat       (most universally compatible)
Step 5  → BibTexFormat    (needs RenanBr parser)
Step 6  → EndNoteXmlFormat (SimpleXML)
Step 7  → AuthorResolver
Step 8  → DuplicateDetector
Step 9  → ReferenceImporter
Step 10 → ReferenceExporter
Step 11 → FormatManager
Step 12 → Migrations + Models (ReferenceCollection, ImportLog)
Step 13 → HasBibliographicExport trait
Step 14 → Artisan commands
Step 15 → Events
Step 16 → Tests
```

---

## Step 1 — composer.json

```json
{
    "name": "shabakah/refmanager",
    "description": "Reference manager with Zotero, EndNote, and Mendeley import/export for Laravel",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "illuminate/support": "^11.0|^12.0",
        "illuminate/database": "^11.0|^12.0",
        "illuminate/console": "^11.0|^12.0",
        "renanbr/bibtex-parser": "^2.1"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "phpunit/phpunit": "^11.0"
    },
    "autoload": {
        "psr-4": {
            "Shabakah\\RefManager\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Shabakah\\RefManager\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Shabakah\\RefManager\\RefManagerServiceProvider"
            ],
            "aliases": {
                "RefImporter": "Shabakah\\RefManager\\Facades\\RefImporter",
                "RefExporter": "Shabakah\\RefManager\\Facades\\RefExporter"
            }
        }
    },
    "minimum-stability": "stable"
}
```

---

## Step 2 — Service Provider

```php
// src/RefManagerServiceProvider.php
namespace Shabakah\RefManager;

use Illuminate\Support\ServiceProvider;
use Shabakah\RefManager\Formats\RisFormat;
use Shabakah\RefManager\Formats\BibTexFormat;
use Shabakah\RefManager\Formats\CslJsonFormat;
use Shabakah\RefManager\Formats\EndNoteXmlFormat;

class RefManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/refmanager.php', 'refmanager');

        $this->app->singleton(FormatManager::class, function ($app) {
            $manager = new FormatManager();
            $manager->register('ris',         RisFormat::class);
            $manager->register('bibtex',      BibTexFormat::class);
            $manager->register('bib',         BibTexFormat::class);  // alias
            $manager->register('csl_json',    CslJsonFormat::class);
            $manager->register('endnote_xml', EndNoteXmlFormat::class);
            return $manager;
        });

        $this->app->singleton(ReferenceImporter::class);
        $this->app->singleton(ReferenceExporter::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/refmanager.php' => config_path('refmanager.php'),
            ], 'refmanager-config');

            $this->commands([
                Commands\ImportCommand::class,
                Commands\ExportCommand::class,
                Commands\DuplicatesCommand::class,
                Commands\FormatsCommand::class,
            ]);
        }
    }
}
```

---

## Step 3 — RisFormat Implementation

Key parsing algorithm — the trickiest part of RIS:

```php
public function parse(string $content): Collection
{
    $records    = [];
    $current    = [];
    $lastTag    = null;

    foreach (explode("\n", $content) as $line) {
        $line = rtrim($line, "\r");

        // End of record
        if (str_starts_with($line, 'ER')) {
            if (!empty($current)) {
                $records[] = $this->normalize($current);
                $current   = [];
                $lastTag   = null;
            }
            continue;
        }

        // Standard tag line: "AB  - value"
        if (preg_match('/^([A-Z][A-Z0-9])\s{2}-\s(.*)$/', $line, $m)) {
            $tag     = $m[1];
            $value   = trim($m[2]);
            $lastTag = $tag;

            // Multi-value tags become arrays
            if (in_array($tag, ['AU', 'A1', 'KW', 'N1'])) {
                $current[$tag][] = $value;
            } else {
                $current[$tag] = $value;
            }
        }
        // Continuation line (no tag) — append to last tag's value
        elseif ($lastTag && !empty(trim($line))) {
            if (is_array($current[$lastTag])) {
                $current[$lastTag][array_key_last($current[$lastTag])] .= ' ' . trim($line);
            } else {
                $current[$lastTag] .= ' ' . trim($line);
            }
        }
    }

    return collect($records);
}
```

### normalize() — RIS → Canonical

```php
private function normalize(array $ris): array
{
    $authors = array_map(
        fn($a) => AuthorResolver::parse($a),
        $ris['AU'] ?? $ris['A1'] ?? []
    );

    $startPage = $ris['SP'] ?? null;
    $endPage   = $ris['EP'] ?? null;
    $pages = match(true) {
        $startPage && $endPage => "{$startPage}-{$endPage}",
        $startPage             => $startPage,
        default                => null,
    };

    $year = $ris['PY'] ?? $ris['Y1'] ?? null;
    // Y1 sometimes: "2024/03/15/"
    if ($year && str_contains($year, '/')) {
        $year = explode('/', $year)[0];
    }

    return [
        'type'           => $this->mapType($ris['TY'] ?? 'GEN'),
        'title'          => $ris['TI'] ?? $ris['T1'] ?? '',
        'abstract'       => $ris['AB'] ?? $ris['N2'] ?? null,
        'DOI'            => $ris['DO'] ?? null,
        'URL'            => $ris['UR'] ?? null,
        'container-title'=> $ris['JO'] ?? $ris['JF'] ?? $ris['T2'] ?? null,
        'volume'         => $ris['VL'] ?? null,
        'issue'          => $ris['IS'] ?? null,
        'page'           => $pages,
        'publisher'      => $ris['PB'] ?? null,
        'publisher-place'=> $ris['CY'] ?? null,
        'language'       => $ris['LA'] ?? null,
        'issued'         => $year ? ['date-parts' => [[(int)$year]]] : null,
        'author'         => $authors,
        'keyword'        => $ris['KW'] ?? [],
        '_raw'           => $ris,
    ];
}
```

---

## Step 4 — BibTexFormat Key Notes

```php
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Listener;

public function parse(string $content): Collection
{
    $parser   = new Parser();
    $listener = new Listener();
    $parser->addListener($listener);
    $parser->parseString($content);

    return collect($listener->export())
        ->filter(fn($e) => ($e['type'] ?? '') !== 'string') // skip @string macros
        ->map(fn($entry) => $this->normalize($entry));
}
```

**Critical BibTeX gotchas:**
- `pages` field uses `--` (double-dash) → normalize to single `-`
- `author` field uses ` and ` as separator → `explode(' and ', $value)`
- Title case preservation: `{CNN}` braces mean "keep this capitalization" → strip braces for storage, restore on export
- `month` field can be a string (`jan`, `feb`) or a number → normalize to int

---

## Step 5 — EndNoteXmlFormat Key Notes

```php
public function parse(string $content): Collection
{
    $xml     = new \SimpleXMLElement($content);
    $records = collect();

    foreach ($xml->records->record as $record) {
        $records->push($this->normalize($record));
    }

    return $records;
}

private function normalize(\SimpleXMLElement $r): array
{
    $authors = [];
    foreach ($r->contributors->authors->author ?? [] as $author) {
        $authors[] = AuthorResolver::parse((string)$author);
    }

    return [
        'type'            => $this->mapRefType((string)$r->{'ref-type'}['name']),
        'title'           => (string)$r->titles->title,
        'abstract'        => (string)$r->abstract ?: null,
        'DOI'             => (string)$r->{'electronic-resource-num'} ?: null,
        'container-title' => (string)$r->titles->{'secondary-title'} ?: null,
        'volume'          => (string)$r->volume ?: null,
        'issue'           => (string)$r->number ?: null,
        'page'            => (string)$r->pages ?: null,
        'publisher'       => (string)$r->publisher ?: null,
        'issued'          => (string)$r->dates->year
                                ? ['date-parts' => [[(int)$r->dates->year]]]
                                : null,
        'author'          => $authors,
        'keyword'         => collect($r->keywords->keyword ?? [])
                                ->map(fn($k) => (string)$k)
                                ->toArray(),
        '_raw'            => [],
    ];
}
```

---

## Step 6 — DuplicateDetector

```php
namespace Shabakah\RefManager\Services;

class DuplicateDetector
{
    public function check(array $canonical, ?int $projectId = null): DuplicateResult
    {
        // Pass 1: exact DOI match
        if ($doi = $canonical['DOI'] ?? null) {
            $existing = Document::where('doi', $this->normalizeDoi($doi))
                ->when($projectId, fn($q) => $q->whereHas('searchProvenance',
                    fn($q2) => $q2->whereHas('searchRun',
                        fn($q3) => $q3->where('project_id', $projectId)
                    )
                ))->first();

            if ($existing) {
                return new DuplicateResult(true, $existing, 1.0, 'doi');
            }
        }

        // Pass 2: title + year Levenshtein fuzzy match
        $title = strtolower(trim($canonical['title'] ?? ''));
        $year  = $canonical['issued']['date-parts'][0][0] ?? null;

        if (strlen($title) > 10) {
            $candidates = Document::where('year', $year)
                ->select(['id', 'title'])
                ->get();

            foreach ($candidates as $candidate) {
                $distance  = levenshtein($title, strtolower($candidate->title));
                $maxLen    = max(strlen($title), strlen($candidate->title));
                $similarity = 1 - ($distance / $maxLen);

                if ($similarity >= 0.92) {
                    return new DuplicateResult(true, $candidate, $similarity, 'title_year');
                }
            }
        }

        return new DuplicateResult(false, null, 0.0, 'none');
    }

    private function normalizeDoi(string $doi): string
    {
        $doi = strtolower(trim($doi));
        $doi = preg_replace('#^https?://(dx\.)?doi\.org/#', '', $doi);
        return $doi;
    }
}
```

