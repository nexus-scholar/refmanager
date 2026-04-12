<?php

namespace Nexus\RefManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\RefManager\Http\Resources\DocumentResource;
use Nexus\RefManager\ReferenceImporter;

class ImportController extends Controller
{
    public function store(Request $request, ReferenceImporter $importer): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['nullable', 'file', 'max:'.(int) config('refmanager.max_upload_size_kb', 20480)],
            'content' => ['nullable', 'string'],
            'format' => ['nullable', 'string'],
            'save' => ['sometimes', 'boolean'],
            'deduplicate' => ['sometimes', 'boolean'],
            'project_id' => ['nullable', 'integer'],
            'collection_id' => ['nullable', 'integer'],
        ]);

        $hasFile = $request->hasFile('file');
        $hasContent = isset($validated['content']) && trim((string) $validated['content']) !== '';

        if (!$hasFile && !$hasContent) {
            return response()->json([
                'message' => 'Provide either an uploaded file or string content with a format.',
            ], 422);
        }

        if ($hasContent && empty($validated['format'])) {
            return response()->json([
                'message' => 'The format field is required when importing from content.',
            ], 422);
        }

        $options = [
            'save' => $validated['save'] ?? false,
            'deduplicate' => $validated['deduplicate'] ?? true,
            'project_id' => $validated['project_id'] ?? null,
            'collection_id' => $validated['collection_id'] ?? null,
        ];

        $result = $importer->withOptions($options);
        $result = $hasFile
            ? $result->fromUpload($request->file('file'))
            : $result->fromString((string) $validated['content'], (string) $validated['format']);

        $duplicates = collect($result->duplicates)
            ->map(fn ($duplicate) => [
                'is_duplicate' => $duplicate->isDuplicate,
                'confidence' => $duplicate->confidence,
                'matched_by' => $duplicate->matchedBy,
                'existing_document_id' => $duplicate->existing?->id,
            ])
            ->values();

        return response()->json([
            'data' => [
                'total' => $result->total(),
                'imported_count' => $result->imported->count(),
                'duplicates_count' => $result->duplicates->count(),
                'failed_count' => $result->failed->count(),
                'imported' => DocumentResource::collection($result->imported->values())->resolve(),
                'duplicates' => $duplicates,
                'failed' => $result->failed->values()->all(),
            ],
        ]);
    }
}

