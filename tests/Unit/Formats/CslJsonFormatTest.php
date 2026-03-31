<?php

namespace Nexus\RefManager\Tests\Unit\Formats;

use Nexus\RefManager\Formats\CslJsonFormat;
use Nexus\RefManager\Tests\TestCase;

class CslJsonFormatTest extends TestCase
{
    private CslJsonFormat $format;

    protected function setUp(): void
    {
        parent::setUp();
        $this->format = new CslJsonFormat();
    }

    public function testItParsesCslJsonArray(): void
    {
        $json = json_encode([[
            'type' => 'article-journal',
            'title' => 'Test Title',
            'author' => [['family' => 'Smith', 'given' => 'John']],
            'issued' => ['date-parts' => [[2024]]],
        ]]);

        $records = $this->format->parse($json);
        $this->assertCount(1, $records);
        $this->assertEquals('Test Title', $records->first()['title']);
    }

    public function testItHandlesItemsEnvelope(): void
    {
        $json = json_encode([
            'items' => [
                ['type' => 'book', 'title' => 'Book One', 'issued' => ['date-parts' => [[2023]]]],
                ['type' => 'book', 'title' => 'Book Two', 'issued' => ['date-parts' => [[2024]]]],
            ]
        ]);

        $records = $this->format->parse($json);
        $this->assertCount(2, $records);
    }

    public function testItThrowsParseExceptionForInvalidJson(): void
    {
        $this->expectException(\Nexus\RefManager\Exceptions\ParseException::class);
        $this->format->parse('not valid json {');
    }

    public function testItThrowsExceptionForNonArrayCslJson(): void
    {
        $this->expectException(\Nexus\RefManager\Exceptions\ParseException::class);
        $this->format->parse('"just a string"');
    }

    public function testItSerializesToPrettyJson(): void
    {
        $canonicals = collect([[
            'type' => 'article-journal',
            'title' => 'Test',
            'author' => [['family' => 'Smith']],
            'issued' => ['date-parts' => [[2024]]],
        ]]);

        $output = $this->format->serialize($canonicals);
        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('Test', $decoded[0]['title']);
    }

    public function testItReturnsCorrectExtensionsAndMimeTypes(): void
    {
        $this->assertEquals(['json'], $this->format->extensions());
        $this->assertEqualsCanonicalizing(
            ['application/vnd.citationstyles.csl+json', 'application/json'],
            $this->format->mimeTypes()
        );
        $this->assertEquals('CSL-JSON', $this->format->label());
    }
}
