<?php

namespace Nexus\RefManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nexus\RefManager\Http\Resources\DocumentResource;
use Nexus\RefManager\Models\Document;

class DocumentsController extends Controller
{
    public function index(Request $request)
    {
        $documentModel = config('refmanager.document_model', Document::class);

        $query = $documentModel::query()->with('authors');

        $statusFilter = trim((string) $request->query('status', ''));
        if ($statusFilter !== '') {
            $statuses = collect(explode(',', $statusFilter))
                ->map(fn ($status) => trim($status))
                ->filter()
                ->values()
                ->all();

            if ($statuses !== [])
                $query->whereIn('status', $statuses);
        }

        $year = $request->integer('year');
        if ($year > 0)
            $query->where('year', $year);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('abstract', 'like', "%{$search}%")
                    ->orWhere('doi', 'like', "%{$search}%")
                    ->orWhere('journal', 'like', "%{$search}%");
            });
        }

        $sort = (string) $request->query('sort', '-year');
        [$column, $direction] = $this->parseSort($sort);
        $query->orderBy($column, $direction)->orderBy('id', 'desc');

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return DocumentResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function show(int $id): DocumentResource
    {
        $documentModel = config('refmanager.document_model', Document::class);
        $document = $documentModel::query()->with('authors')->findOrFail($id);

        return new DocumentResource($document);
    }

    public function update(Request $request, int $id): DocumentResource
    {
        $documentModel = config('refmanager.document_model', Document::class);
        $document = $documentModel::query()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string'],
            'abstract' => ['sometimes', 'nullable', 'string'],
            'year' => ['sometimes', 'nullable', 'integer'],
            'doi' => ['sometimes', 'nullable', 'string'],
            'url' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', $this->allowedStatuses())],
            'exclusion_reason' => ['sometimes', 'nullable', 'string'],
        ]);

        $document->fill($validated);
        $document->save();

        $document->load('authors');

        return new DocumentResource($document);
    }

    public function destroy(int $id)
    {
        $documentModel = config('refmanager.document_model', Document::class);
        $documentModel::query()->findOrFail($id)->delete();

        return response()->noContent();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function parseSort(string $sort): array
    {
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $allowedColumns = ['id', 'title', 'year', 'status', 'created_at', 'updated_at'];
        if (!in_array($column, $allowedColumns, true))
            $column = 'year';

        return [$column, $direction];
    }

    /**
     * @return array<int,string>
     */
    private function allowedStatuses(): array
    {
        return [
            Document::STATUS_IMPORTED,
            Document::STATUS_TITLE_ABSTRACT_SCREENED,
            Document::STATUS_FULL_TEXT_SCREENED,
            Document::STATUS_INCLUDED,
            Document::STATUS_EXCLUDED,
        ];
    }
}

