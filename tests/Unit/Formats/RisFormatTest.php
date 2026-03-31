<?php

namespace Nexus\RefManager\Tests\Unit\Formats;

use Nexus\RefManager\Formats\RisFormat;
use Nexus\RefManager\Tests\TestCase;

class RisFormatTest extends TestCase
{
    private RisFormat $format;

    protected function setUp(): void
    {
        parent::setUp();
        $this->format = new RisFormat();
    }

    public function testItParsesASingleRisRecord(): void
    {
        $ris = <<<RIS
TY  - JOUR
TI  - Plant Disease Detection with CNN
AU  - Smith, John
AU  - Doe, Jane
AB  - Abstract text here.
DO  - 10.1000/test.001
PY  - 2024
JO  - Test Journal
VL  - 5
IS  - 2
SP  - 100
EP  - 115
KW  - CNN
KW  - plant disease
ER  -
RIS;

        $records = $this->format->parse($ris);

        $this->assertCount(1, $records);
        $r = $records->first();
        $this->assertEquals('Plant Disease Detection with CNN', $r['title']);
        $this->assertCount(2, $r['author']);
        $this->assertEquals('Smith', $r['author'][0]['family']);
        $this->assertEquals('10.1000/test.001', $r['DOI']);
        $this->assertEquals('100-115', $r['page']);
        $this->assertEquals(['CNN', 'plant disease'], $r['keyword']);
        $this->assertEquals([2024], $r['issued']['date-parts'][0]);
    }

    public function testItParsesMultipleRecords(): void
    {
        $ris = file_get_contents(__DIR__.'/../../fixtures/multiple_records.ris');
        $records = $this->format->parse($ris);
        $this->assertCount(5, $records);
    }

    public function testItHandlesAbstractContinuationLines(): void
    {
        $ris = "TY  - JOUR\nTI  - Title\nAB  - First line\n  second line\nER  -\n";
        $r = $this->format->parse($ris)->first();
        $this->assertStringContainsString('First line', $r['abstract']);
        $this->assertStringContainsString('second line', $r['abstract']);
    }

    public function testItMapsRisTypesCorrectly(): void
    {
        $types = [
            'JOUR' => 'article-journal',
            'CONF' => 'paper-conference',
            'CPAPER' => 'paper-conference',
            'BOOK' => 'book',
            'CHAP' => 'chapter',
            'THES' => 'thesis',
            'RPRT' => 'report',
            'ELEC' => 'webpage',
            'GEN' => 'article',
        ];

        foreach ($types as $risType => $expectedCsl) {
            $ris = "TY  - {$risType}\nTI  - Test Title\nER  -\n";
            $r = $this->format->parse($ris)->first();
            $this->assertEquals($expectedCsl, $r['type'], "Failed for RIS type: {$risType}");
        }
    }

    public function testItExportsBackToValidRis(): void
    {
        $canonicals = collect([[
            'type' => 'article-journal',
            'title' => 'Test Title',
            'author' => [['family' => 'Smith', 'given' => 'John']],
            'DOI' => '10.1000/xyz',
            'issued' => ['date-parts' => [[2024]]],
            'container-title' => 'Test Journal',
            'keyword' => ['ai', 'ml'],
        ]]);

        $output = $this->format->serialize($canonicals);

        $this->assertStringContainsString('TY  - JOUR', $output);
        $this->assertStringContainsString('TI  - Test Title', $output);
        $this->assertStringContainsString('AU  - Smith, John', $output);
        $this->assertStringContainsString('DO  - 10.1000/xyz', $output);
        $this->assertStringContainsString('ER  -', $output);
    }

    public function testItHandlesYearWithSlash(): void
    {
        $ris = "TY  - JOUR\nTI  - Title\nPY  - 2024/03/15/\nER  -\n";
        $r = $this->format->parse($ris)->first();
        $this->assertEquals(2024, $r['issued']['date-parts'][0][0]);
    }

    public function testItReturnsCorrectExtensionsAndMimeTypes(): void
    {
        $this->assertEquals(['ris'], $this->format->extensions());
        $this->assertEquals(['application/x-research-info-systems'], $this->format->mimeTypes());
        $this->assertEquals('RIS', $this->format->label());
    }
}
