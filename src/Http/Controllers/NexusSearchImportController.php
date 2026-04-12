<?php

namespace Nexus\RefManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Nexus\RefManager\Http\Resources\DocumentResource;
use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Services\AuthorResolver;
use Nexus\RefManager\Services\DuplicateDetector;

class NexusSearchImportController extends Controller
{
    public function __construct(
        private readonly DuplicateDetector $duplicateDetector,
        private readonly AuthorResolver $authorResolver,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string'],
            'year_min' => ['nullable', 'integer'],
            'year_max' => ['nullable', 'integer'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:500'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'language' => ['nullable', 'string', 'max:10'],
            'deduplicate' => ['sometimes', 'boolean'],
            'project_id' => ['nullable', 'integer'],
            'collection_id' => ['nullable', 'integer'],
            'use_cache' => ['sometimes', 'boolean'],
        ]);

        $nexusSearcherClass = 'Nexus\\Laravel\\NexusSearcher';
        $nexusQueryClass = 'Nexus\\Models\\Query';

        if (! class_exists($nexusSearcherClass) || ! class_exists($nexusQueryClass)) {
            return response()->json([
                'message' => 'nexus/nexus-php is not installed. Install and configure nexus-php to use this endpoint.',
            ], 422);
        }

        if (! app()->bound($nexusSearcherClass)) {
            return response()->json([
                'message' => 'NexusSearcher is not bound in the container. Verify nexus-php service provider registration.',
            ], 422);
        }

        $query = new $nexusQueryClass(
            text: (string) $validated['query'],
            yearMin: $validated['year_min'] ?? null,
            yearMax: $validated['year_max'] ?? null,
            language: (string) ($validated['language'] ?? 'en'),
            maxResults: $validated['max_results'] ?? null,
            offset: $validated['offset'] ?? 0,
        );

        $searcher = app($nexusSearcherClass);
        $results = $searcher->search(
            $query,
            ['openalex'],
            (bool) ($validated['use_cache'] ?? true)
        );

        $documentModel = config('refmanager.document_model', Document::class);
        $deduplicate = (bool) ($validated['deduplicate'] ?? true);

        $imported = collect();
        $duplicates = collect();
        $failed = collect();

        foreach ($results as $index => $result) {
            try {
                $canonical = $this->toCanonical($result);

                if ($deduplicate) {
                    $duplicate = $this->duplicateDetector->check(
                        $canonical,
                        $validated['project_id'] ?? null,
                    );

                    if ($duplicate->isDuplicate) {
                        $duplicates->push([
                            'matched_by' => $duplicate->matchedBy,
                            'confidence' => $duplicate->confidence,
                            'existing_document_id' => $duplicate->existing?->id,
                        ]);

                        continue;
                    }
                }

                $document = $this->persistDocument(
                    modelClass: (string) $documentModel,
                    result: $result,
                    canonical: $canonical,
                    queryId: (string) ($query->id ?? ''),
                    queryText: (string) ($query->text ?? ''),
                    collectionId: $validated['collection_id'] ?? null,
                );

                $imported->push($document->load('authors'));
            } catch (\Throwable $e) {
                $failed->push([
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'data' => [
                'provider' => 'openalex',
                'searched_count' => count($results),
                'imported_count' => $imported->count(),
                'duplicates_count' => $duplicates->count(),
                'failed_count' => $failed->count(),
                'imported' => DocumentResource::collection($imported)->resolve(),
                'duplicates' => $duplicates->values()->all(),
                'failed' => $failed->values()->all(),
            ],
        ]);
    }

    private function toCanonical(object $result): array
    {
        $authors = collect($result->authors ?? [])
            ->map(fn ($author) => [
                'family' => (string) ($author->familyName ?? ''),
                'given' => $author->givenName ?? null,
            ])
            ->all();

        $year = isset($result->year) && is_numeric($result->year)
            ? (int) $result->year
            : null;

        return [
            'title' => (string) ($result->title ?? ''),
            'DOI' => $result->externalIds?->doi ?? null,
            'PMID' => $result->externalIds?->pubmedId ?? null,
            'issued' => $year !== null && $year > 0 ? ['date-parts' => [[$year]]] : null,
            'author' => $authors,
        ];
    }

    private function persistDocument(
        string $modelClass,
        object $result,
        array $canonical,
        string $queryId,
        string $queryText,
        ?int $collectionId,
    ): object {
        $importedStatus = $this->resolveImportedStatus($modelClass);

        $document = $modelClass::query()->updateOrCreate(
            [
                'provider' => (string) ($result->provider ?? 'openalex'),
                'provider_id' => (string) ($result->providerId ?? ''),
            ],
            [
                'title' => (string) ($result->title ?? ''),
                'abstract' => $result->abstract ?? null,
                'doi' => $result->externalIds?->doi ?? null,
                'url' => $result->url ?? null,
                'journal' => $result->venue ?? null,
                'language' => $result->language ?? null,
                'year' => $result->year ?? null,
                'document_type' => 'article',
                'provider' => (string) ($result->provider ?? 'openalex'),
                'provider_id' => (string) ($result->providerId ?? ''),
                'arxiv_id' => $result->externalIds?->arxivId ?? null,
                'openalex_id' => $result->externalIds?->openalexId ?? null,
                's2_id' => $result->externalIds?->s2Id ?? null,
                'pubmed_id' => $result->externalIds?->pubmedId ?? null,
                'cited_by_count' => $result->citedByCount ?? null,
                'query_id' => $queryId,
                'query_text' => $queryText,
                'retrieved_at' => now(),
                'raw_data' => method_exists($result, 'toArray') ? $result->toArray() : null,
                'status' => $importedStatus,
            ]
        );

        $this->syncAuthors($document, collect($canonical['author'] ?? []));

        if ($collectionId !== null) {
            $document->referenceCollections()->syncWithoutDetaching([$collectionId => ['added_at' => now()]]);
        }

        return $document;
    }

    private function resolveImportedStatus(string $modelClass): string
    {
        return defined($modelClass.'::STATUS_IMPORTED')
            ? (string) constant($modelClass.'::STATUS_IMPORTED')
            : Document::STATUS_IMPORTED;
    }

    private function syncAuthors(object $document, Collection $authors): void
    {
        $authorIds = [];

        foreach ($authors as $index => $authorData) {
            $author = $this->authorResolver->resolve($authorData);
            $authorIds[$author->id] = ['author_order' => $index + 1];
        }

        if ($authorIds !== []) {
            $document->authors()->sync($authorIds);
        }
    }
}
