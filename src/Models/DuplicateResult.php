<?php

namespace Nexus\RefManager\Models;

final class DuplicateResult
{
    public function __construct(
        public readonly bool      $isDuplicate,
        public readonly mixed     $existing,      // ?Document
        public readonly float     $confidence,    // 0.0–1.0
        public readonly string    $matchedBy,     // 'doi' | 'title_year' | 'none'
    ) {}
}
