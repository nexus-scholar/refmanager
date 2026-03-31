<?php

namespace Nexus\RefManager\Commands;

use Illuminate\Console\Command;
use Nexus\RefManager\FormatManager;

class FormatsCommand extends Command
{
    protected $signature = 'refmanager:formats';
    protected $description = 'Show supported bibliographic formats';

    public function handle(FormatManager $manager): int
    {
        $formats = $manager->all();
        $rows = [];

        foreach ($formats as $name => $class) {
            $instance = app($class);
            $rows[] = [
                $name,
                $instance->label(),
                implode(', ', $instance->extensions()),
                implode(', ', $instance->mimeTypes()),
            ];
        }

        $this->table(['Key', 'Name', 'Extensions', 'MIME Types'], $rows);

        return 0;
    }
}
