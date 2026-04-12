<?php

namespace Nexus\RefManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Nexus\RefManager\Http\Resources\DocumentResource;
use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Support\TitleNormalizer;

class DeduplicationController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer'],
            'threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $documentModel = (string) config('refmanager.document_model', Document::class);
        if ($documentModel === '') {
            $documentModel = Document::class;
        }
        $year = (int) ($validated['year'] ?? now()->year);
        $scanLimit = max(1, (int) config('refmanager.deduplication.scan_limit', 1000));
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? config('refmanager.deduplication.scan_per_page', 50));

        $threshold = (float) ($validated['threshold'] ?? config('refmanager.deduplication.fuzzy_threshold', 0.92));

        $documents = $documentModel::query()
            ->select(['id', 'title', 'year', 'status', 'doi'])
            ->with('authors')
            ->where('year', $year)
            ->orderBy('id')
            ->limit($scanLimit + 1)
            ->get();

        $scanLimitReached = $documents->count() > $scanLimit;
        if ($scanLimitReached) {
            $documents = $documents->take($scanLimit)->values();
        }

        $pairs = [];
        $matchedCount = 0;
        $offset = ($page - 1) * $perPage;
        $maxMatchedBeforeStop = $offset + $perPage + 1;
        $hasMore = false;
        $count = $documents->count();

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $documents[$i];
                $right = $documents[$j];

                $leftTitle = TitleNormalizer::normalize((string) $left->title);
                $rightTitle = TitleNormalizer::normalize((string) $right->title);

                if ($leftTitle === '' || $rightTitle === '') {
                    continue;
                }

                $maxLen = max(strlen($leftTitle), strlen($rightTitle));
                if ($maxLen === 0) {
                    continue;
                }

                $distance = levenshtein($leftTitle, $rightTitle);
                $similarity = 1 - ($distance / $maxLen);

                if ($similarity < $threshold) {
                    continue;
                }

                if ($matchedCount >= $offset && count($pairs) < $perPage) {
                    $pairs[] = [
                        'confidence' => round($similarity, 4),
                        'matched_by' => 'fuzzy_title_year_review',
                        'primary' => (new DocumentResource($left))->resolve(),
                        'candidate' => (new DocumentResource($right))->resolve(),
                    ];
                }

                $matchedCount++;
                if ($matchedCount >= $maxMatchedBeforeStop) {
                    $hasMore = true;
                    break 2;
                }
            }
        }

        if ($scanLimitReached) {
            $hasMore = true;
        }

        return response()->json([
            'data' => [
                'year' => $year,
                'threshold' => $threshold,
                'scan_limit' => $scanLimit,
                'scan_limit_reached' => $scanLimitReached,
                'scanned_documents' => $documents->count(),
                'count' => count($pairs),
                'pairs' => $pairs,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'has_more' => $hasMore,
                    'offset' => $offset,
                ],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->scan($request);
    }

    public function resolve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:merge,keep_both'],
            'primary_id' => ['required', 'integer'],
            'candidate_ids' => ['required', 'array', 'min:1'],
            'candidate_ids.*' => ['integer'],
        ]);

        $documentModel = (string) config('refmanager.document_model', Document::class);
        if ($documentModel === '') {
            $documentModel = Document::class;
        }

        $primary = $documentModel::query()->findOrFail((int) $validated['primary_id']);

        $candidateIds = collect($validated['candidate_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === (int) $primary->id)
            ->values();

        $candidates = $documentModel::query()
            ->whereIn('id', $candidateIds->all())
            ->get();

        if ($candidateIds->count() !== $candidates->count()) {
            return response()->json([
                'message' => 'Some candidate IDs do not exist.',
            ], 422);
        }

        if ($validated['action'] === 'keep_both') {
            return response()->json([
                'data' => [
                    'action' => 'keep_both',
                    'primary_id' => $primary->id,
                    'resolved_count' => 0,
                ],
            ]);
        }

        $excludedStatus = defined($documentModel.'::STATUS_EXCLUDED')
            ? (string) constant($documentModel.'::STATUS_EXCLUDED')
            : Document::STATUS_EXCLUDED;

        DB::transaction(function () use ($documentModel, $candidateIds, $primary, $excludedStatus) {
            $documentModel::query()
                ->whereIn('id', $candidateIds->all())
                ->update([
                    'merged_into_id' => $primary->id,
                    'status' => $excludedStatus,
                    'exclusion_reason' => 'duplicate_merged',
                ]);
        });

        return response()->json([
            'data' => [
                'action' => 'merge',
                'primary_id' => $primary->id,
                'resolved_count' => $candidateIds->count(),
            ],
        ]);
    }
}
