<?php

namespace Nexus\RefManager\Commands;

use Illuminate\Console\Command;
use Nexus\RefManager\Models\ReferenceCollection;
use Nexus\RefManager\ReferenceExporter;

class ExportCommand extends Command
{
    protected $signature = 'refmanager:export {--project=} {--collection=} {--format=ris} {--output=}';
    protected $description = 'Export a project\'s documents';

    public function handle(ReferenceExporter $exporter): int
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

        if ($this->option('collection')) {
            $collectionId = $this->option('collection');
            $query->whereHas('referenceCollections', function($q) use ($collectionId) {
                $q->where('reference_collections.id', $collectionId);
            });
        }

        $documents = $query->get();
        $this->info("Exporting " . $documents->count() . " documents...");

        $output = $exporter->toString($documents, $this->option('format'));

        if ($this->option('output')) {
            file_put_contents($this->option('output'), $output);
            $this->info("Export saved to: " . $this->option('output'));
        } else {
            $this->line($output);
        }

        return 0;
    }
}
