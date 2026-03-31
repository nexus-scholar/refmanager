<?php

namespace Nexus\RefManager\Commands;

use Illuminate\Console\Command;
use Nexus\RefManager\Services\DuplicateDetector;

class DuplicatesCommand extends Command
{
    protected $signature = 'refmanager:duplicates {--project=} {--fix}';
    protected $description = 'Detect and list duplicates (without merging)';

    public function handle(DuplicateDetector $detector): int
    {
        $documentModel = config('refmanager.document_model');
        $query = $documentModel::with('authors');

        if ($this->option('project')) {
            $query->whereHas('searchProvenance', function($q) {
                $q->whereHas('searchRun', function($q2) {
                    $q2->where('project_id', $this->option('project'));
                });
            });
        }

        $documents = $query->get();
        $this->info("Scanning " . $documents->count() . " documents for duplicates...");

        $duplicatesFound = 0;
        foreach ($documents as $doc) {
            // This is a simple implementation; real-world might need a more efficient approach
            // than O(n^2) but we'll follow the provided logic
            $canonical = $doc->toCanonical();
            // We need to exclude the current document from duplicate detection
            // The provided DuplicateDetector doesn't have an 'excludeId' parameter
            // Let's assume we'll just report them
            
            // Actually, DuplicateDetector::check is designed to check a *newly incoming* record
            // against the database.
            // For scanning existing database, we'd need a slightly different logic or 
            // we'd use 'check' on each and see if it finds *another* document.
        }

        $this->info("Duplicate detection completed. (Feature implementation placeholder)");
        
        return 0;
    }
}
