<?php

namespace Nexus\RefManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'orcid',
    ];

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            config('refmanager.document_model'),
            'document_author',
            'author_id',
            'document_id'
        )->withPivot('author_order');
    }
}
