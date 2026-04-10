<?php

namespace Nexus\RefManager\Tests\Unit\Commands;

use Illuminate\Support\Facades\File;
use Nexus\RefManager\Tests\TestCase;

class UiInstallCommandTest extends TestCase
{
    public function testItScaffoldsWorkspaceFiles(): void
    {
        $relativePath = 'tests/tmp-ui-install';
        $absolutePath = base_path($relativePath);

        if (File::isDirectory($absolutePath))
            File::deleteDirectory($absolutePath);

        try {
            $this->artisan('refmanager:ui-install', [
                '--path' => $relativePath,
                '--force' => true,
            ])->assertExitCode(0);

            $this->assertFileExists($absolutePath.'/Workspace.tsx');
            $this->assertFileExists($absolutePath.'/refmanager-route-snippet.php');
        } finally {
            if (File::isDirectory($absolutePath))
                File::deleteDirectory($absolutePath);
        }
    }
}

