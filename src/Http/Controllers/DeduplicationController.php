<?php

namespace Nexus\RefManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Nexus\RefManager\Http\Resources\DocumentResource;
use Nexus\RefManager\Models\Document;

class DeduplicationController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer'],
            'threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $documentModel = config('refmanager.document_model', Document::class);

        $threshold = (float) ($validated['threshold'] ?? config('refmanager.deduplication.fuzzy_threshold', 0.92));

        $documents = $documentModel::query()
            ->select(['id', 'title', 'year', 'status', 'doi'])
            ->with('authors')
            ->when(isset($validated['year']), fn ($query) => $query->where('year', $validated['year']))
            ->whereNotNull('year')
            ->orderBy('year')
            ->orderBy('id')
            ->get();

        $pairs = [];
        $count = $documents->count();

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $documents[$i];
                $right = $documents[$j];

                if ((int) $left->year !== (int) $right->year)
                    continue;

                $leftTitle = $this->normalizeTitle((string) $left->title);
                $rightTitle = $this->normalizeTitle((string) $right->title);

                if ($leftTitle === '' || $rightTitle === '')
                    continue;

                $maxLen = max(strlen($leftTitle), strlen($rightTitle));
                if ($maxLen === 0)
                    continue;

                $distance = levenshtein($leftTitle, $rightTitle);
                $similarity = 1 - ($distance / $maxLen);

                if ($similarity < $threshold)
                    continue;

                $pairs[] = [
                    'confidence' => round($similarity, 4),
                    'matched_by' => 'fuzzy_title_year_review',
                    'primary' => (new DocumentResource($left))->resolve(),
                    'candidate' => (new DocumentResource($right))->resolve(),
                ];
            }
        }

        return response()->json([
            'data' => [
                'threshold' => $threshold,
                'count' => count($pairs),
                'pairs' => $pairs,
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

        $documentModel = config('refmanager.document_model', Document::class);

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

        DB::transaction(function () use ($documentModel, $candidateIds, $primary) {
            $documentModel::query()
                ->whereIn('id', $candidateIds->all())
                ->update([
                    'merged_into_id' => $primary->id,
                    'status' => Document::STATUS_EXCLUDED,
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

    private function normalizeTitle(string $title): string
    {
        $title = strtolower(trim($title));
        return preg_replace('/\s+/u', ' ', $title) ?? $title;
    }
}

