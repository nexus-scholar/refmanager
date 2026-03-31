<?php

namespace Nexus\RefManager\Tests\Unit\Formats;

use Nexus\RefManager\Formats\EndNoteXmlFormat;
use Nexus\RefManager\Tests\TestCase;

class EndNoteXmlFormatTest extends TestCase
{
    private EndNoteXmlFormat $format;

    protected function setUp(): void
    {
        parent::setUp();
        $this->format = new EndNoteXmlFormat();
    }

    public function testItParsesEndnoteXmlRecords(): void
    {
        $xml = file_get_contents(__DIR__.'/../../fixtures/sample.xml');
        $records = $this->format->parse($xml);
        $this->assertCount(1, $records);
    }

    public function testItExtractsTitleAndAuthors(): void
    {
        $xml = '<?xml version="1.0"?><xml><records><record>
            <ref-type name="Journal Article">17</ref-type>
            <contributors><authors><author>Smith, John</author></authors></contributors>
            <titles><title>Test Title</title></titles>
        </record></records></xml>';

        $records = $this->format->parse($xml);
        $r = $records->first();
        $this->assertEquals('Test Title', $r['title']);
        $this->assertEquals('Smith', $r['author'][0]['family']);
    }

    public function testItExtractsDoi(): void
    {
        $xml = '<?xml version="1.0"?><xml><records><record>
            <ref-type name="Journal Article">17</ref-type>
            <titles><title>Title</title></titles>
            <electronic-resource-num>10.1234/test</electronic-resource-num>
        </record></records></xml>';

        $r = $this->format->parse($xml)->first();
        $this->assertEquals('10.1234/test', $r['DOI']);
    }

    public function testItExtractsKeywords(): void
    {
        $xml = '<?xml version="1.0"?><xml><records><record>
            <ref-type name="Journal Article">17</ref-type>
            <titles><title>Title</title></titles>
            <keywords><keyword>ai</keyword><keyword>ml</keyword></keywords>
        </record></records></xml>';

        $r = $this->format->parse($xml)->first();
        $this->assertEquals(['ai', 'ml'], $r['keyword']);
    }

    public function testItReturnsCorrectExtensionsAndMimeTypes(): void
    {
        $this->assertEquals(['xml'], $this->format->extensions());
        $this->assertEquals(['application/xml', 'text/xml'], $this->format->mimeTypes());
        $this->assertEquals('EndNote XML', $this->format->label());
    }
}
