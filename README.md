# Nexus RefManager

[![Tests](https://github.com/nexus-scholar/refmanager/workflows/Tests/badge.svg)](https://github.com/nexus-scholar/refmanager/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/nexus/refmanager.svg)](https://packagist.org/packages/nexus/refmanager)
[![License](https://img.shields.io/packagist/license/nexus/refmanager.svg)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/nexus/refmanager.svg)](https://packagist.org/packages/nexus/refmanager)

Nexus RefManager is a Laravel package for importing, normalizing, deduplicating, organizing, and exporting bibliographic references. It supports RIS, BibTeX, CSL-JSON, and EndNote XML, with command-line and service APIs designed for systematic-review and research-workflow applications.

## Role In Nexus Scholar

`refmanager` is a focused package in the Nexus Scholar ecosystem. It handles reference-library boundaries around import/export and citation metadata, while `nexus-scholar/core` owns review workflow behavior such as search, corpus locking, screening, full-text retrieval, citation graphs, and export auditing.

Use this package when a Laravel app needs dependable bibliographic file handling without pulling in the full Nexus Scholar workflow engine.

## Features

- Multi-format import and export for RIS, BibTeX, CSL-JSON, and EndNote XML.
- DOI-first and title/year fallback deduplication.
- Collection management with notes and tagging.
- Event hooks for logging and post-processing.
- Streaming exports for large collections.
- Artisan commands for import, export, dry runs, and format discovery.
- Optional project-scoped deduplication through a configurable callback.

## Status

| Component | Status |
| --- | --- |
| RIS parser/exporter | Stable |
| BibTeX parser/exporter | Stable |
| CSL-JSON parser/exporter | Stable |
| EndNote XML parser/exporter | Stable |
| Deduplication | Stable |
| Events and logging | Stable |
| Streaming exports | Stable |
| Facade helpers | Experimental |

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- SQLite for the default test setup

## Installation

```bash
composer require nexus/refmanager
php artisan vendor:publish --tag=refmanager-config
php artisan migrate
```

Add the export trait to the model that represents a bibliographic document in your application:

```php
use Nexus\RefManager\Concerns\HasBibliographicExport;

class Document extends Model
{
    use HasBibliographicExport;
}
```

## Quick Start

Import references from a file:

```php
use Nexus\RefManager\ReferenceImporter;

$result = app(ReferenceImporter::class)
    ->withOptions(['deduplicate' => true, 'save' => true])
    ->fromFile('library.ris');
```

Import from a raw string with an explicit format:

```php
$result = app(ReferenceImporter::class)
    ->fromString($risContent, 'ris');
```

Export references as a string or streaming download:

```php
use Nexus\RefManager\ReferenceExporter;

$ris = app(ReferenceExporter::class)->toString($documents, 'ris');

return app(ReferenceExporter::class)
    ->toResponse($documents, 'bibtex', 'library.bib');
```

## Artisan Commands

```bash
php artisan refmanager:import library.ris --project=1
php artisan refmanager:import library.ris --dry-run
php artisan refmanager:export --project=1 --format=ris --output=library.ris
php artisan refmanager:formats
```

## Supported Formats

| Format | Extension | Import | Export |
| --- | --- | :---: | :---: |
| RIS | `.ris` | Yes | Yes |
| BibTeX | `.bib` | Yes | Yes |
| CSL-JSON | `.json` | Yes | Yes |
| EndNote XML | `.xml` | Yes | Yes |

## Import Options

```php
$result = $importer->withOptions([
    'deduplicate' => true,
    'save' => false,
    'project_id' => 7,
    'collection_id' => 42,
])->fromFile('/path/to/library.bib');
```

The import result exposes parsed documents, imported records, duplicates, failed records, and the import log:

```php
$result->documents;
$result->imported;
$result->duplicates;
$result->failed;
$result->log;
$result->total();
$result->count();
$result->wasSuccessful();
```

## Project-Scoped Deduplication

By default, `project_id` is passed through the importer and API, but no project filter is applied unless you configure one. This keeps the package schema-agnostic for apps that do not store project ownership on the document table.

After publishing config, set `refmanager.deduplication.project_scope` to a callable:

```php
'deduplication' => [
    'project_scope' => static function ($query, int $projectId): void {
        $query->where('project_id', $projectId);
    },
],
```

The callback receives the Eloquent query builder and incoming `project_id`; it is applied to DOI, PubMed, and title/year deduplication tiers. If `project_scope` is `null`, deduplication remains global.

## Experimental Facade Helpers

```php
use Nexus\RefManager\Support\ExporterBuilder;

$ris = ExporterBuilder::documents($documents)->asRis();
$bibtex = ExporterBuilder::project(7)->asBibtex();
$json = ExporterBuilder::collection($collection)->asCslJson();

return ExporterBuilder::documents($documents)->download('ris', 'library.ris');
```

## Testing

```bash
composer install
composer test
./vendor/bin/phpunit --testdox
./vendor/bin/phpunit tests/Unit/Formats/RisFormatTest.php
./vendor/bin/phpunit --filter=testItParsesASingleRisRecord
```

## License

Nexus RefManager is open-sourced software licensed under the [MIT license](LICENSE).
