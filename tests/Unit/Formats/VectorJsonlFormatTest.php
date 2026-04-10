<?php

namespace Nexus\RefManager\Tests\Unit\Formats;

use Nexus\RefManager\Formats\VectorJsonlFormat;
use Nexus\RefManager\Tests\TestCase;

class VectorJsonlFormatTest extends TestCase
{
    private VectorJsonlFormat $format;

    protected function setUp(): void
    {
        parent::setUp();
        $this->format = new VectorJsonlFormat();
    }

    public function testItSerializesToVectorJsonl(): void
    {
        $canonicals = collect([
            [
                'type' => 'article-journal',
                'title' => 'AI for SLR',
                'abstract' => 'Automating title and abstract screening.',
                'DOI' => '10.1000/test',
                'URL' => 'https://example.org/paper',
                'issued' => ['date-parts' => [[2025]]],
                'keyword' => ['slr', 'automation'],
            ],
        ]);

        $output = $this->format->serialize($canonicals);
        $lines = preg_split('/\r\n|\r|\n/', trim($output));

        $this->assertCount(1, $lines);
        $decoded = json_decode($lines[0], true);

        $this->assertSame('AI for SLR', $decoded['title']);
        $this->assertSame('10.1000/test', $decoded['doi']);
        $this->assertStringContainsString('AI for SLR', $decoded['text']);
    }

    public function testItParsesJsonlToCanonical(): void
    {
        $input = json_encode([
            'type' => 'article-journal',
            'title' => 'A title',
            'abstract' => 'An abstract',
            'doi' => '10.1234/abc',
            'url' => 'https://example.org/abc',
            'year' => 2024,
            'keywords' => ['health'],
        ]);

        $records = $this->format->parse($input);

        $this->assertCount(1, $records);
        $this->assertSame('A title', $records[0]['title']);
        $this->assertSame('10.1234/abc', $records[0]['DOI']);
        $this->assertSame(2024, $records[0]['issued']['date-parts'][0][0]);
    }

    public function testItReturnsExtensionsAndMimeTypes(): void
    {
        $this->assertSame(['jsonl', 'ndjson'], $this->format->extensions());
        $this->assertEqualsCanonicalizing(['application/x-ndjson', 'application/jsonl'], $this->format->mimeTypes());
        $this->assertSame('Vector JSONL', $this->format->label());
    }
}

