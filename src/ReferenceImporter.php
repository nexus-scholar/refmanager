<?php

namespace Nexus\RefManager;

use Illuminate\Http\UploadedFile;
use Nexus\RefManager\Events\ImportCompleted;
use Nexus\RefManager\Events\ImportStarted;
use Nexus\RefManager\Exceptions\ParseException;
use Nexus\RefManager\Formats\Contracts\ReferenceFormat;
use Nexus\RefManager\Models\ImportLog;
use Nexus\RefManager\Models\ImportResult;
use Nexus\RefManager\Services\AuthorResolver;
use Nexus\RefManager\Services\DuplicateDetector;

class ReferenceImporter
{
    private array $options = [
        'deduplicate' => true,
        'save' => false,
        'project_id' => null,
        'collection_id' => null,
    ];

    public function __construct(
        private readonly FormatManager $formatManager,
        private readonly DuplicateDetector $duplicateDetector,
        private readonly AuthorResolver $authorResolver,
    ) {}

    /**
     * Returns a cloned importer with merged options.
     * Always use the returned instance for the import call.
     */
    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->options = array_merge($clone->options, $options);

        return $clone;
    }

    public function fromFile(string $path): ImportResult
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $format = $this->formatManager->byExtension($ext);
        $content = file_get_contents($path);

        return $this->process($content, $format, basename($path));
    }

    public function fromUpload(UploadedFile $file): ImportResult
    {
        $format = $this->formatManager->fromUpload($file);
        $content = $file->get();

        return $this->process($content, $format, $file->getClientOriginalName());
    }

    public function fromString(string $content, string $formatName): ImportResult
    {
        $format = $this->formatManager->byName($formatName);

        return $this->process($content, $format);
    }

    private function process(string $content, ReferenceFormat $format, ?string $filename = null): ImportResult
    {
        event(new ImportStarted($format->label(), $filename, $this->options));

        $canonicals = $format->parse($content);
        $documentModel = config('refmanager.document_model');

        $imported = collect();
        $duplicates = collect();
        $failed = collect();

        foreach ($canonicals as $index => $canonical) {
            try {
                if ($this->options['deduplicate']) {
                    $dupResult = $this->duplicateDetector->check(
                        $canonical, $this->options['project_id']
                    );
                    if ($dupResult->isDuplicate) {
                        $duplicates->push($dupResult);

                        continue;
                    }
                }

                $document = $this->hydrate($canonical, $documentModel);

                if ($this->options['save']) {
                    $document->save();
                    $this->attachAuthors($document, $canonical['author'] ?? []);
                }

                $imported->push($document);
            } catch (ParseException $e) {
                $failed->push([
                    'record' => $canonical,
                    'error' => $e->getMessage(),
                    'meta' => $e->getMeta(),
                    'index' => $index,
                ]);
            } catch (\Throwable $e) {
                $failed->push([
                    'record' => $canonical,
                    'error' => $e->getMessage(),
                    'index' => $index,
                ]);
            }
        }

        $log = $this->writeLog(
            $format->label(),
            $filename,
            $canonicals->count(),
            $imported->count(),
            $duplicates->count(),
            $failed->count()
        );

        $result = new ImportResult($canonicals, $imported, $duplicates, $failed, $log);

        event(new ImportCompleted($result));

        return $result;
    }

    private function hydrate(array $canonical, string $documentModel): mixed
    {
        $doc = new $documentModel;
        $documentType = $canonical['type'] ?? 'article';
        $containerTitle = $canonical['container-title'] ?? null;
        $isChapterType = in_array($documentType, ['chapter', 'entry-dictionary', 'entry-encyclopedia'], true);

        $doc->title = $canonical['title'] ?? '';
        $doc->abstract = $canonical['abstract'] ?? null;
        $doc->doi = $canonical['DOI'] ?? null;
        $doc->url = $canonical['URL'] ?? null;
        $doc->journal = $isChapterType ? null : $containerTitle;
        $doc->book_title = $isChapterType ? $containerTitle : ($canonical['book_title'] ?? null);
        $doc->volume = $canonical['volume'] ?? null;
        $doc->issue = $canonical['issue'] ?? null;
        $doc->pages = $canonical['page'] ?? null;
        $doc->publisher = $canonical['publisher'] ?? null;
        $doc->publisher_place = $canonical['publisher-place'] ?? null;
        $doc->language = $canonical['language'] ?? null;
        $doc->year = $canonical['issued']['date-parts'][0][0] ?? null;
        $doc->keywords = $canonical['keyword'] ?? [];
        $doc->document_type = $documentType;

        return $doc;
    }

    private function attachAuthors(mixed $document, array $authors): void
    {
        foreach ($authors as $order => $authorData) {
            $author = $this->authorResolver->resolve($authorData);
            $document->authors()->attach($author->id, ['author_order' => $order + 1]);
        }
    }

    private function writeLog(string $format, ?string $filename, int $total, int $imported, int $duplicates, int $failed): ?ImportLog
    {
        if (! config('refmanager.log_imports', true)) {
            return null;
        }

        return ImportLog::create([
            'format' => $format,
            'filename' => $filename,
            'total_parsed' => $total,
            'imported' => $imported,
            'duplicates' => $duplicates,
            'failed' => $failed,
            'collection_id' => $this->options['collection_id'],
        ]);
    }
}
