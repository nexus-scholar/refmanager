<?php

namespace Nexus\RefManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'status',
        'exclusion_reason',
        'merged_into_id',
    ];

    protected $casts = [
        'keywords' => 'array',
        'raw_data' => 'array',
        'retrieved_at' => 'datetime',
    ];

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_TITLE_ABSTRACT_SCREENED = 'title_abstract_screened';

    public const STATUS_FULL_TEXT_SCREENED = 'full_text_screened';

    public const STATUS_INCLUDED = 'included';

    public const STATUS_EXCLUDED = 'excluded';

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

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function mergedChildren(): HasMany
    {
        return $this->hasMany(self::class, 'merged_into_id');
    }

    public function citations(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'document_citations',
            'citing_document_id',
            'cited_document_id'
        )->withPivot('source', 'weight')->withTimestamps();
    }

    public function citedBy(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'document_citations',
            'cited_document_id',
            'citing_document_id'
        )->withPivot('source', 'weight')->withTimestamps();
    }
}
