<?php

namespace Nexus\RefManager\Services;

use Nexus\RefManager\Models\DuplicateResult;
use Nexus\RefManager\Support\TitleNormalizer;

class DuplicateDetector
{
    public function check(array $canonical, ?int $projectId = null): DuplicateResult
    {
        $documentModel = config('refmanager.document_model');

        // Tier 1: Exact DOI or PubMed match => auto-merge
        if ($doi = $canonical['DOI'] ?? null) {
            $normalizedDoi = $this->normalizeDoi($doi);

            $existing = $documentModel::where('doi', $normalizedDoi)
                ->when($projectId, function ($q) use ($projectId) {
                    // Logic for project scope if applicable
                })->first();

            if ($existing) {
                return new DuplicateResult(true, $existing, 1.0, 'doi');
            }
        }

        $pmid = trim((string) ($canonical['PMID'] ?? ''));
        if ($pmid !== '') {
            $existing = $documentModel::where('pubmed_id', $pmid)
                ->when($projectId, function ($q) use ($projectId) {
                    // Logic for project scope if applicable
                })->first();

            if ($existing) {
                return new DuplicateResult(true, $existing, 1.0, 'pubmed');
            }
        }

        $title = TitleNormalizer::normalize((string) ($canonical['title'] ?? ''));
        $year = $canonical['issued']['date-parts'][0][0] ?? null;
        $canonicalLastNames = $this->extractAuthorLastNames($canonical['author'] ?? []);

        if ($title !== '' && $year !== null) {
            $candidates = $documentModel::where('year', $year)
                ->with('authors:id,family_name')
                ->get(['id', 'title']);

            // Tier 2: Exact title + same year + at least one author last-name overlap => auto-merge
            foreach ($candidates as $candidate) {
                $candidateTitle = TitleNormalizer::normalize((string) $candidate->title);
                if ($candidateTitle !== $title) {
                    continue;
                }

                if ($canonicalLastNames === []) {
                    continue;
                }

                $candidateLastNames = $candidate->authors
                    ->pluck('family_name')
                    ->filter()
                    ->map(fn ($name) => strtolower(trim((string) $name)))
                    ->unique()
                    ->values()
                    ->all();

                if (count(array_intersect($canonicalLastNames, $candidateLastNames)) > 0) {
                    return new DuplicateResult(true, $candidate, 0.98, 'title_year_author');
                }
            }

            // Tier 3: Fuzzy title + same year => flag for review, no auto-merge
            $threshold = (float) config('refmanager.deduplication.fuzzy_threshold', 0.92);
            foreach ($candidates as $candidate) {
                $candidateTitle = TitleNormalizer::normalize((string) $candidate->title);
                if ($candidateTitle === '') {
                    continue;
                }

                $distance = levenshtein($title, $candidateTitle);
                $maxLen = max(strlen($title), strlen($candidateTitle));
                if ($maxLen === 0) {
                    continue;
                }

                $similarity = 1 - ($distance / $maxLen);
                if ($similarity >= $threshold) {
                    return new DuplicateResult(false, $candidate, $similarity, 'fuzzy_title_year_review');
                }
            }
        }

        return new DuplicateResult(false, null, 0.0, 'none');
    }


    private function extractAuthorLastNames(array $authors): array
    {
        return collect($authors)
            ->map(fn ($author) => strtolower(trim((string) ($author['family'] ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeDoi(string $doi): string
    {
        $doi = strtolower(trim($doi));
        $doi = preg_replace('#^https?://(dx\.)?doi\.org/#', '', $doi);
        return $doi;
    }
}
