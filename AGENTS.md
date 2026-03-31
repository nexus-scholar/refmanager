# AGENTS.md

## Nexus RefManager - Agent Guidelines

> Laravel package for bibliographic reference import/export (RIS, BibTeX, CSL-JSON, EndNote XML).

---

## Project Overview

- **Namespace**: `Nexus\RefManager`
- **PHP**: 8.2+
- **Laravel**: 11.x / 12.x / 13.x
- **Autoload**: `Nexus\RefManager\\` → `src/`, `Nexus\RefManager\Tests\\` → `tests/`

### Key Dependencies
- `illuminate/support`, `illuminate/database`, `illuminate/console`
- `renanbr/bibtex-parser` for BibTeX parsing
- Dev: `orchestra/testbench`, `phpunit/phpunit`

---

## Build / Test Commands

```bash
# Install dependencies
composer install

# Run all tests
composer test
# or directly:
./vendor/bin/phpunit

# Run single test class
./vendor/bin/phpunit tests/Unit/Formats/RisFormatTest.php

# Run single test method
./vendor/bin/phpunit --filter="it_parses_a_single_ris_record"

# Run tests with coverage (if configured)
./vendor/bin/phpunit --coverage-html coverage/
```

**Note**: No linting/formatting tools configured (no PHPStan, PHP-CS-Fixer, or PHPCS). Tests use Orchestra Testbench with SQLite in-memory database.

---

## Code Style Guidelines

### General
- PHP 8.2+ features: constructor property promotion, readonly properties, match expressions
- No braces on single-line control structures
- Use `strict_types=1` is NOT enforced (no declare statement in files)
- PSR-4 autoloading

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `ReferenceImporter`, `DuplicateDetector` |
| Methods | camelCase | `fromFile()`, `checkDuplicates()` |
| Properties | camelCase | `$options`, `$formatManager` |
| Constants | SCREAMING_SNAKE_CASE | `TYPE_MAP`, `FUZZY_THRESHOLD` |
| Private helpers | camelCase | `hydrate()`, `attachAuthors()` |
| Interfaces | PascalCase with suffix | `ReferenceFormat` |
| Traits | PascalCase with suffix | `HasBibliographicExport` |
| Test classes | PascalCase + Test suffix | `RisFormatTest` |
| Test methods | camelCase with `it_` prefix | `it_parses_single_record()` |

### File Organization

```
src/
├── Commands/          # Artisan commands
├── Concerns/          # Traits
├── Events/            # Event classes
├── Exceptions/        # Custom exceptions
├── Facades/           # Facade classes
├── Formats/
│   └── Contracts/    # Interfaces
├── Models/            # Eloquent models
└── Services/          # Business logic services
```

### Imports

```php
// Group by: native → third-party → local
use Illuminate\Support\Collection;           // Laravel
use Illuminate\Database\Eloquent\Model;       // Laravel
use Nexus\RefManager\Models\Document;        // Local
use Nexus\RefManager\Services\AuthorResolver; // Local
```

### Type Declarations

- **Always use return types** on public methods
- **Use union types** where appropriate (PHP 8.0+)
- **Use nullable types** with `?` prefix
- **Use `mixed`** sparingly, prefer specific types
- **Use `array<string, ClassName>`** for typed arrays

```php
// Good
public function byName(string $name): ReferenceFormat
public function check(array $canonical, ?int $projectId = null): DuplicateResult
private array $formats = [];

// Avoid
public function parse($content)           // No type
public function byName($name)              // No return type
```

### Constructor Property Promotion

Use readonly for injected dependencies that shouldn't change:

```php
// Good
public function __construct(
    private readonly FormatManager $formatManager,
    private readonly DuplicateDetector $duplicateDetector,
) {}

// Avoid
private $formatManager;
public function __construct(FormatManager $fm) {
    $this->formatManager = $fm;
}
```

### Collection Usage

- Use `Illuminate\Support\Collection` for all chainable operations
- Use `collect()` helper for arrays
- Use arrow functions (`fn()`) for simple callbacks

```php
// Good
return collect($records)->map(fn($r) => $this->normalize($r))->filter(fn($r) => !empty($r));

// Avoid
$coll = collect(); foreach ($records as $r) { $coll->push($this->normalize($r)); }
```

### Error Handling

- Use custom exceptions for domain errors
- Throw `ParseException` for format parsing failures
- Throw `UnsupportedFormatException` for unknown formats
- Catch `\Throwable` for recovery in batch operations

```php
// Domain exceptions
throw new UnsupportedFormatException($name);

// Parse errors
throw new ParseException(
    message: 'Invalid JSON: ' . json_last_error_msg(),
    format: 'csl_json',
    rawRecord: $content,
);

// Recovery in loops
try {
    $document = $this->hydrate($canonical);
} catch (\Throwable $e) {
    $failed->push(['record' => $canonical, 'error' => $e->getMessage()]);
}
```

### Custom Exception Pattern

```php
class ParseException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $format,
        public readonly ?string $rawRecord = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
```

---

## Architecture Patterns

### ReferenceFormat Interface

All format parsers must implement this contract:

```php
interface ReferenceFormat
{
    public function parse(string $content): Collection;
    public function serialize(Collection $canonicals): string;
    public function extensions(): array;
    public function mimeTypes(): array;
    public function label(): string;
}
```

### Canonical Format (CSL-JSON)

Internal representation uses CSL-JSON schema:

```php
[
    'type'            => 'article-journal',  // CSL type
    'title'           => '...',
    'author'          => [['family' => 'Smith', 'given' => 'John']],
    'issued'          => ['date-parts' => [[2024]]],
    'container-title' => 'Journal Name',
    'DOI'             => '10.1234/...',
    // ...
    '_raw'            => [],  // Original parsed data
]
```

### DTO/Value Objects

Use `final class` with `readonly` constructor for immutable data:

```php
final class DuplicateResult
{
    public function __construct(
        public readonly bool $isDuplicate,
        public readonly mixed $existing,
        public readonly float $confidence,
        public readonly string $matchedBy,
    ) {}
}
```

### Event Pattern

All import/export operations fire events:

```php
event(new ImportStarted($format->label(), $filename, $options));
// ... process ...
event(new ImportCompleted($result));
```

---

## Testing Conventions

### Test Structure

```php
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
        TI  - Title
        ER  -
        RIS;

        $records = $this->format->parse($ris);

        $this->assertCount(1, $records);
    }
}
```

### Test Fixtures Location

```
tests/fixtures/
├── sample.ris
├── multiple_records.ris
├── sample.bib
├── sample.json
└── duplicates.ris
```

### Test Database

SQLite in-memory via Orchestra Testbench:

```php
protected function getEnvironmentSetUp($app): void
{
    $app['config']->set('database.default', 'testing');
    $app['config']->set('database.connections.testing', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
    ]);
}
```

---

## Service Provider Registration

Services are registered as singletons in `RefManagerServiceProvider`:

```php
$this->app->singleton(FormatManager::class, function ($app) {
    $manager = new FormatManager();
    $manager->register('ris', RisFormat::class);
    // ...
    return $manager;
});
```

Format classes are resolved via `app()` from the container, allowing dependency injection in format classes if needed.

---

## Configuration

Published via `config/refmanager.php`:

```php
return [
    'document_model' => Document::class,
    'author_model' => Author::class,
    'deduplication' => [
        'enabled' => true,
        'fuzzy_threshold' => 0.92,
    ],
    'log_imports' => true,
];
```

Use `config('refmanager.*')` to access settings.
