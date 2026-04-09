<?php

namespace Nexus\RefManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Nexus\RefManager\Concerns\HasBibliographicExport;

class Document extends Model
{
    use HasBibliographicExport;

    protected $fillable = [
        'title',
        'abstract',
        'doi',
        'url',
        'journal',
        'book_title',
        'volume',
        'issue',
        'pages',
        'publisher',
        'publisher_place',
        'language',
        'year',
        'keywords',
        'document_type',
        'provider',
        'provider_id',
        'arxiv_id',
        'openalex_id',
        's2_id',
        'pubmed_id',
        'cited_by_count',
        'query_id',
        'query_text',
        'retrieved_at',
        'cluster_id',
        'raw_data',
    ];

    protected $casts = [
        'keywords' => 'array',
        'raw_data' => 'array',
        'retrieved_at' => 'datetime',
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            config('refmanager.author_model'),
            'document_author',
            'document_id',
            'author_id'
        )->withPivot('author_order');
    }

    public function referenceCollections(): BelongsToMany
    {
        return $this->belongsToMany(
            ReferenceCollection::class,
            'reference_collection_document',
            'document_id',
            'collection_id'
        )->withPivot('added_at', 'note');
    }
}
