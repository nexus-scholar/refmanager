<?php

namespace Nexus\RefManager\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nexus\RefManager\FormatManager;
use Nexus\RefManager\Models\ReferenceCollection;

trait HasBibliographicExport
{
    /**
     * Convert this Document to the canonical CSL-JSON array.
     */
    public function toCanonical(): array
    {
        return [
            'type'            => $this->resolveType(),
            'title'           => $this->title,
            'abstract'        => $this->abstract,
            'DOI'             => $this->doi,
            'URL'             => $this->url,
            'language'        => $this->language,
            'container-title' => $this->journal ?? $this->book_title,
            'volume'          => $this->volume,
            'issue'           => $this->issue,
            'page'            => $this->pages,
            'publisher'       => $this->publisher,
            'publisher-place' => $this->publisher_place,
            'issued'          => $this->formatIssuedDate(),
            'author'          => $this->authors
                                    ->sortBy('pivot.author_order')
                                    ->map(fn($a) => [
                                        'family' => $a->last_name,
                                        'given'  => $a->first_name,
                                        'ORCID'  => $a->orcid ?? null,
                                    ])->values()->toArray(),
            'keyword'         => $this->keywords ?? [],
        ];
    }

    public function toRis(): string
    {
        return app(FormatManager::class)->byName('ris')->serialize(collect([$this->toCanonical()]));
    }

    public function toBibTex(): string
    {
        return app(FormatManager::class)->byName('bibtex')->serialize(collect([$this->toCanonical()]));
    }

    public function toCslJson(): string
    {
        return app(FormatManager::class)->byName('csl_json')->serialize(collect([$this->toCanonical()]));
    }

    public function toEndNoteXml(): string
    {
        return app(FormatManager::class)->byName('endnote_xml')->serialize(collect([$this->toCanonical()]));
    }

    public function referenceCollections(): BelongsToMany
    {
        return $this->belongsToMany(ReferenceCollection::class, 'reference_collection_document', 'document_id', 'collection_id')
                    ->withPivot('added_at', 'note');
    }

    protected function resolveType(): string
    {
        return $this->document_type ?? 'article';
    }

    protected function formatIssuedDate(): ?array
    {
        if (!$this->year) return null;
        return ['date-parts' => [[(int)$this->year]]];
    }
}
