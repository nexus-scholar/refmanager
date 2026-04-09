<?php

namespace Nexus\RefManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    protected $fillable = [
        'given_name',
        'family_name',
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

    public function getFullName(): string
    {
        if ($this->given_name) {
            return "{$this->given_name} {$this->family_name}";
        }

        return (string) $this->family_name;
    }
}
