<?php

namespace Nexus\RefManager\Commands;

use Illuminate\Console\Command;
use Nexus\RefManager\ReferenceImporter;

class ImportCommand extends Command
{
    protected $signature = 'refmanager:import {file} {--project=} {--collection=} {--format=} {--dry-run}';
    protected $description = 'Import a file into a project\'s document library';

    public function handle(ReferenceImporter $importer): int
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $options = [
            'project_id'    => $this->option('project'),
            'collection_id' => $this->option('collection'),
            'save'          => !$this->option('dry-run'),
        ];

        $formatName = $this->option('format');
        
        $this->info("Parsing " . basename($file) . "...");
        
        if ($formatName) {
            $result = $importer->withOptions($options)->fromString(file_get_contents($file), $formatName);
        } else {
            $result = $importer->withOptions($options)->fromFile($file);
        }

        $this->table(
            ['Parsed', 'Imported', 'Duplicates', 'Failed'],
            [[$result->total(), $result->imported->count(), $result->duplicates->count(), $result->failed->count()]]
        );

        if ($result->failed->isNotEmpty()) {
            foreach ($result->failed as $fail) {
                $this->error("Error: " . $fail['error']);
            }
        }

        if ($this->option('dry-run')) {
            $this->warn("Dry run: no changes were saved to the database.");
        }

        return 0;
    }
}
