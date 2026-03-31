<?php

namespace Nexus\RefManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferenceCollection extends Model
{
    protected $table = 'reference_collections';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function documents(): BelongsToMany
    {
        $documentModel = config('refmanager.document_model');
        return $this->belongsToMany($documentModel, 'reference_collection_document', 'collection_id', 'document_id')
                    ->withPivot('added_at', 'note');
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class, 'collection_id');
    }
}
