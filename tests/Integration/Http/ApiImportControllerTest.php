<?php

namespace Nexus\RefManager\Tests\Integration\Http;

use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Tests\TestCase;

class ApiImportControllerTest extends TestCase
{
    public function test_it_defaults_to_dry_run_when_save_flag_is_omitted(): void
    {
        $ris = "TY  - JOUR\nTI  - Dry Run Import\nPY  - 2024\nER  -\n";

        $response = $this->postJson('/api/refmanager/import', [
            'content' => $ris,
            'format' => 'ris',
            'deduplicate' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.imported_count', 1);

        $this->assertDatabaseMissing('documents', [
            'title' => 'Dry Run Import',
        ]);
        $this->assertSame(0, Document::count());
    }
}

