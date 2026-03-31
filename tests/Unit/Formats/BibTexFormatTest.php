<?php

namespace Nexus\RefManager\Tests\Unit\Formats;

use Nexus\RefManager\Formats\BibTexFormat;
use Nexus\RefManager\Tests\TestCase;

class BibTexFormatTest extends TestCase
{
    private BibTexFormat $format;

    protected function setUp(): void
    {
        parent::setUp();
        $this->format = new BibTexFormat();
    }

    public function testItParsesBibtexWithAndSeparatedAuthors(): void
    {
        $bib = '@article{smith2024, title={Test}, author={Smith, John and Doe, Jane}, year={2024}}';
        $records = $this->format->parse($bib);
        $this->assertCount(1, $records);
        $this->assertCount(2, $records->first()['author']);
    }

    public function testItNormalizesDoubleDashPages(): void
    {
        $bib = '@article{x, title={T}, pages={100--115}, year={2024}}';
        $r = $this->format->parse($bib)->first();
        $this->assertEquals('100-115', $r['page']);
    }

    public function testItStripsBracesFromTitle(): void
    {
        $bib = '@article{x, title={My Title}, year={2024}}';
        $r = $this->format->parse($bib)->first();
        $this->assertEquals('My Title', $r['title']);
    }

    public function testItMapsEntryTypesToCsl(): void
    {
        $types = [
            'article' => 'article-journal',
            'inproceedings' => 'paper-conference',
            'conference' => 'paper-conference',
            'book' => 'book',
            'incollection' => 'chapter',
            'phdthesis' => 'thesis',
            'mastersthesis' => 'thesis',
            'techreport' => 'report',
        ];

        foreach ($types as $bibType => $expectedCsl) {
            $bib = "@{$bibType}{test, title={Title}, year={2024}}";
            $r = $this->format->parse($bib)->first();
            $this->assertEquals($expectedCsl, $r['type'], "Failed for BibTeX type: {$bibType}");
        }
    }

    public function testItParsesFullBibtexFile(): void
    {
        $bib = file_get_contents(__DIR__.'/../../fixtures/sample.bib');
        $records = $this->format->parse($bib);
        $this->assertCount(3, $records);
    }

    public function testItSerializesToBibtex(): void
    {
        $canonicals = collect([[
            'type' => 'article-journal',
            'title' => 'Test Article',
            'author' => [['family' => 'Smith', 'given' => 'John']],
            'issued' => ['date-parts' => [[2024]]],
            'container-title' => 'Test Journal',
        ]]);

        $output = $this->format->serialize($canonicals);

        $this->assertStringContainsString('@article{', $output);
        $this->assertStringContainsString('title = {Test Article}', $output);
        $this->assertStringContainsString('author = Smith, John', $output);
    }

    public function testItReturnsCorrectExtensionsAndMimeTypes(): void
    {
        $this->assertEquals(['bib', 'bibtex'], $this->format->extensions());
        $this->assertEquals(['application/x-bibtex'], $this->format->mimeTypes());
        $this->assertEquals('BibTeX', $this->format->label());
    }
}
