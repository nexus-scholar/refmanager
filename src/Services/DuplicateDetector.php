<?php

namespace Nexus\RefManager\Services;

use Nexus\RefManager\Models\DuplicateResult;

class DuplicateDetector
{
    public function check(array $canonical, ?int $projectId = null): DuplicateResult
    {
        $documentModel = config('refmanager.document_model');
        
        // Pass 1: exact DOI match
        if ($doi = $canonical['DOI'] ?? null) {
            $normalizedDoi = $this->normalizeDoi($doi);
            
            $existing = $documentModel::where('doi', $normalizedDoi)
                ->when($projectId, function($q) use ($projectId) {
                    // Logic for project scope if applicable
                    // This depends on how Document relates to projects
                })->first();

            if ($existing) {
                return new DuplicateResult(true, $existing, 1.0, 'doi');
            }
        }

        // Pass 2: title + year Levenshtein fuzzy match
        $title = strtolower(trim($canonical['title'] ?? ''));
        $year  = $canonical['issued']['date-parts'][0][0] ?? null;

        if (strlen($title) > 10) {
            $candidates = $documentModel::where('year', $year)
                ->select(['id', 'title'])
                ->get();

            $threshold = config('refmanager.deduplication.fuzzy_threshold', 0.92);

            foreach ($candidates as $candidate) {
                $distance  = levenshtein($title, strtolower($candidate->title));
                $maxLen    = max(strlen($title), strlen($candidate->title));
                
                if ($maxLen === 0) continue;
                
                $similarity = 1 - ($distance / $maxLen);

                if ($similarity >= $threshold) {
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
