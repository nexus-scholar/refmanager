<?php

namespace Nexus\RefManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $table = 'import_logs';

    protected $fillable = [
        'format',
        'filename',
        'total_parsed',
        'imported',
        'duplicates',
        'failed',
        'collection_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ReferenceCollection::class, 'collection_id');
    }
}
