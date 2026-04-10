<?php

namespace Nexus\RefManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class UiInstallCommand extends Command
{
    protected $signature = 'refmanager:ui-install
        {--path=resources/js/Pages/RefManager : Target page directory in host app}
        {--api-base-url=/api/refmanager : API base URL passed to RefManagerProvider}
        {--force : Overwrite existing files}';

    protected $description = 'Scaffold a starter RefManager UI workspace page for Laravel/Inertia projects';

    public function handle(Filesystem $files): int
    {
        $targetPath = trim((string) $this->option('path'));
        $apiBaseUrl = trim((string) $this->option('api-base-url'));
        $force = (bool) $this->option('force');

        if ($targetPath === '') {
            $this->error('The --path option cannot be empty.');
            return self::FAILURE;
        }

        $directory = base_path($targetPath);
        $workspaceFile = $directory.'/Workspace.tsx';
        $routeSnippetFile = $directory.'/refmanager-route-snippet.php';

        if (!$files->isDirectory($directory))
            $files->makeDirectory($directory, 0755, true);

        if ($files->exists($workspaceFile) && !$force) {
            $this->warn('Workspace.tsx already exists. Use --force to overwrite.');
            return self::INVALID;
        }

        $workspaceStub = $files->get(__DIR__.'/../../stubs/ui/Workspace.tsx.stub');
        $workspaceContent = str_replace('{{apiBaseUrl}}', $apiBaseUrl, $workspaceStub);

        $files->put($workspaceFile, $workspaceContent);

        $routeSnippet = $files->get(__DIR__.'/../../stubs/ui/route-snippet.stub');
        $files->put($routeSnippetFile, $routeSnippet);

        $this->info('RefManager UI starter files generated:');
        $this->line('- '.$workspaceFile);
        $this->line('- '.$routeSnippetFile);

        $this->newLine();
        $this->comment('Next steps:');
        $this->line('1) Install UI package: npm install @nexus/refmanager-ui');
        $this->line('2) Add Tailwind content path for @nexus/refmanager-ui files');
        $this->line('3) Register the route from '.$routeSnippetFile.' in your routes/web.php');

        return self::SUCCESS;
    }
}

