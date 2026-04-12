<?php

use Nexus\RefManager\Models\Author;
use Nexus\RefManager\Models\Document;

return [
    'document_model' => Document::class,
    'author_model' => Author::class,

    'api' => [
        'prefix' => 'api/refmanager',
        'middleware' => ['api'],
    ],

    'deduplication' => [
        'enabled' => true,
        'doi_exact' => true,
        'title_year_fuzzy' => true,
        'fuzzy_threshold' => 0.92,
        'scan_limit' => 1000,
        'scan_per_page' => 50,
        'project_scope' => null, // callable(Builder $query, int $projectId): void
    ],

    'max_upload_size_kb' => 20480,
    'export_chunk_size' => 500,
    'log_imports' => true,
];
