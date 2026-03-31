# Nexus RefManager

[![Tests](https://github.com/nexus-scholar/refmanager/workflows/Tests/badge.svg)](https://github.com/nexus-scholar/refmanager/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/nexus/refmanager.svg)](https://packagist.org/packages/nexus/refmanager)
[![Laravel Version](https://img.shields.io/packagist/illuminate/laravel/11.svg)](https://laravel.com)
[![License](https://img.shields.io/packagist/license/nexus/refmanager.svg)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/nexus/refmanager.svg)](https://packagist.org/packages/nexus/refmanager)

> A Laravel package for managing bibliographic references with full import/export support for Zotero, EndNote, and Mendeley.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Usage](#usage)
- [Supported Formats](#supported-formats)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Features

- **Multi-format Import/Export** - RIS, BibTeX, CSL-JSON, and EndNote XML
- **Smart Deduplication** - DOI exact match + title+year fuzzy matching
- **Collection Management** - Named reference lists with notes and tagging
- **Event-Driven** - Fire events for logging and post-processing hooks
- **Streaming Exports** - Memory-safe exports for large collections
- **SLR Integration** - Filter and export screened/included documents

---

## Status

> **v1.0** - Stable. Parsers, importer, and exporter are production-ready.

| Component | Status |
|-----------|--------|
| RIS Parser/Exporter | ✅ Stable |
| BibTeX Parser/Exporter | ✅ Stable |
| CSL-JSON Parser/Exporter | ✅ Stable |
| EndNote XML Parser/Exporter | ✅ Stable |
| Deduplication | ✅ Stable |
| Events & Logging | ✅ Stable |
| Streaming Exports | ✅ Stable |
| Facade Helpers | 🧪 Experimental |

---

## Requirements

- PHP 8.2+
- Laravel 11.x / 12.x / 13.x
- SQLite (for testing)

---

## Installation

### Via Composer

```bash
composer require nexus/refmanager
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=refmanager-config
```

### Run Migrations

```bash
php artisan migrate
```

### Add the Trait to Your Document Model

```php
use Nexus\RefManager\Concerns\HasBibliographicExport;

class Document extends Model
{
    use HasBibliographicExport;
}
```

---

## Quick Start

### Import References

```php
use Nexus\RefManager\ReferenceImporter;

// Import from file (auto-detects format)
$result = app(ReferenceImporter::class)
    ->withOptions(['deduplicate' => true, 'save' => true])
    ->fromFile('library.ris');

// Import from string with explicit format
$result = app(ReferenceImporter::class)
    ->fromString($risContent, 'ris');
```

### Export References

```php
use Nexus\RefManager\ReferenceExporter;

// Export as string
$ris = app(ReferenceExporter::class)->toString($documents, 'ris');

// Export as streaming download
return app(ReferenceExporter::class)
    ->toResponse($documents, 'bibtex', 'library.bib');
```

### Artisan Commands

```bash
# Import a file
php artisan refmanager:import library.ris --project=1

# Dry-run import
php artisan refmanager:import library.ris --dry-run

# Export documents
php artisan refmanager:export --project=1 --format=ris --output=library.ris

# List supported formats
php artisan refmanager:formats
```

---

## Supported Formats

| Format | Extension | MIME Type | Import | Export |
|--------|-----------|-----------|:------:|:------:|
| RIS | `.ris` | `application/x-research-info-systems` | ✅ | ✅ |
| BibTeX | `.bib` | `application/x-bibtex` | ✅ | ✅ |
| CSL-JSON | `.json` | `application/vnd.citationstyles.csl+json` | ✅ | ✅ |
| EndNote XML | `.xml` | `application/xml` | ✅ | ✅ |

---

## API Reference

### ReferenceImporter

```php
// From file (auto-detects format by extension)
$result = $importer->fromFile('/path/to/library.ris');

// From uploaded file
$result = $importer->fromUpload($request->file('library'));

// From raw string
$result = $importer->fromString($content, 'ris');

// With options
$result = $importer->withOptions([
    'deduplicate'   => true,   // default: true
    'save'          => false,  // default: false (return unsaved models)
    'project_id'    => 7,
    'collection_id' => 42,
])->fromFile('/path/to/library.bib');
```

### ImportResult

```php
$result->documents;     // All parsed records (including duplicates)
$result->imported;     // Net-new documents
$result->duplicates;   // Duplicate records found
$result->failed;       // Records that failed to parse
$result->log;         // ImportLog audit record
$result->total();      // Total count
$result->count();      // Imported count
$result->wasSuccessful(); // bool (no failures)
```

### ReferenceExporter

```php
// As string
$ris = $exporter->toString($documents, 'ris');

// As streaming response
return $exporter->toResponse($documents, 'bibtex', 'library.bib');

// From collection
return $exporter->fromCollection($collection, 'csl_json');
```

### Expressive Facade Helpers (Experimental)

```php
use Nexus\RefManager\Support\ExporterBuilder;

// From documents collection
$ris = ExporterBuilder::documents($documents)->asRis();

// From a specific project
$bibtex = ExporterBuilder::project(7)->asBibtex();

// From a collection
$json = ExporterBuilder::collection($collection)->asCslJson();

// Download as file
return ExporterBuilder::documents($documents)->download('ris', 'library.ris');
```

---

## Testing

```bash
# Install dependencies
composer install

# Run all tests
composer test
# or
./vendor/bin/phpunit

# Run with testdox output
./vendor/bin/phpunit --testdox

# Run single test file
./vendor/bin/phpunit tests/Unit/Formats/RisFormatTest.php

# Run single test method
./vendor/bin/phpunit --filter=testItParsesASingleRisRecord
```

---

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## License

The Nexus RefManager package is open-sourced software licensed under the [MIT license](LICENSE).
