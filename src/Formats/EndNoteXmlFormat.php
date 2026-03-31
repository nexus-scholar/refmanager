<?php

namespace Nexus\RefManager\Formats;

use Illuminate\Support\Collection;
use Nexus\RefManager\Exceptions\ParseException;
use Nexus\RefManager\Formats\Contracts\ReferenceFormat;
use Nexus\RefManager\Services\AuthorResolver;

class EndNoteXmlFormat implements ReferenceFormat
{
    private const TYPE_MAP = [
        'Journal Article' => 'article-journal',
        'Conference Paper' => 'paper-conference',
        'Book'            => 'book',
        'Book Section'    => 'chapter',
        'Thesis'          => 'thesis',
        'Report'          => 'report',
        'Web Page'        => 'webpage',
        'Generic'         => 'article',
    ];

    public function parse(string $content): Collection
    {
        try {
            $xml = new \SimpleXMLElement($content);
        } catch (\Exception $e) {
            throw new ParseException('Invalid EndNote XML: ' . $e->getMessage(), 'endnote_xml', null, $e);
        }

        $records = collect();
        foreach ($xml->records->record ?? [] as $record) {
            $records->push($this->normalize($record));
        }
        return $records;
    }

    public function serialize(Collection $canonicals): string
    {
        $dom  = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root    = $dom->createElement('xml');
        $records = $dom->createElement('records');
        $dom->appendChild($root)->appendChild($records);

        foreach ($canonicals as $c) {
            $rec = $dom->createElement('record');

            $refType = $dom->createElement('ref-type', $this->reverseMapType($c['type'] ?? 'article'));
            $refType->setAttribute('name', $this->cslTypeToEndnoteLabel($c['type'] ?? 'article'));
            $rec->appendChild($refType);

            // Titles
            $titlesEl = $dom->createElement('titles');
            $titlesEl->appendChild($dom->createElement('title', htmlspecialchars($c['title'] ?? '')));
            if (!empty($c['container-title'])) {
                $titlesEl->appendChild($dom->createElement('secondary-title', htmlspecialchars($c['container-title'])));
            }
            $rec->appendChild($titlesEl);

            // Authors
            if (!empty($c['author'])) {
                $contribs  = $dom->createElement('contributors');
                $authorsEl = $dom->createElement('authors');
                foreach ($c['author'] as $a) {
                    $name = isset($a['literal']) ? $a['literal'] : (($a['family'] ?? '') . ', ' . ($a['given'] ?? ''));
                    $authorsEl->appendChild($dom->createElement('author', htmlspecialchars(trim($name, ', '))));
                }
                $contribs->appendChild($authorsEl);
                $rec->appendChild($contribs);
            }

            if (!empty($c['abstract'])) $rec->appendChild($dom->createElement('abstract', htmlspecialchars($c['abstract'])));
            if (!empty($c['DOI']))      $rec->appendChild($dom->createElement('electronic-resource-num', $c['DOI']));
            if (!empty($c['volume']))   $rec->appendChild($dom->createElement('volume', $c['volume']));
            if (!empty($c['issue']))    $rec->appendChild($dom->createElement('number', $c['issue']));
            if (!empty($c['page']))     $rec->appendChild($dom->createElement('pages', $c['page']));
            if (!empty($c['publisher'])) $rec->appendChild($dom->createElement('publisher', htmlspecialchars($c['publisher'])));

            $year = $c['issued']['date-parts'][0][0] ?? null;
            if ($year) {
                $dates = $dom->createElement('dates');
                $dates->appendChild($dom->createElement('year', $year));
                $rec->appendChild($dates);
            }

            if (!empty($c['keyword'])) {
                $kwsEl = $dom->createElement('keywords');
                foreach ($c['keyword'] as $kw) {
                    $kwsEl->appendChild($dom->createElement('keyword', htmlspecialchars($kw)));
                }
                $rec->appendChild($kwsEl);
            }

            $records->appendChild($rec);
        }

        return $dom->saveXML();
    }

    public function extensions(): array  { return ['xml']; }
    public function mimeTypes(): array   { return ['application/xml', 'text/xml']; }
    public function label(): string      { return 'EndNote XML'; }

    private function normalize(\SimpleXMLElement $r): array
    {
        $authors = [];
        foreach ($r->contributors->authors->author ?? [] as $author) {
            $authors[] = AuthorResolver::parse((string)$author);
        }

        $typeName = (string)($r->{'ref-type'}['name'] ?? 'Generic');

        return [
            'type'            => self::TYPE_MAP[$typeName] ?? 'article',
            'title'           => (string)($r->titles->title ?? ''),
            'abstract'        => (string)($r->abstract ?? '') ?: null,
            'DOI'             => (string)($r->{'electronic-resource-num'} ?? '') ?: null,
            'container-title' => (string)($r->titles->{'secondary-title'} ?? '') ?: null,
            'volume'          => (string)($r->volume ?? '') ?: null,
            'issue'           => (string)($r->number ?? '') ?: null,
            'page'            => (string)($r->pages ?? '') ?: null,
            'publisher'       => (string)($r->publisher ?? '') ?: null,
            'issued'          => (string)($r->dates->year ?? '')
                                    ? ['date-parts' => [[(int)$r->dates->year]]]
                                    : null,
            'author'          => $authors,
            'keyword'         => $this->extractKeywords($r),
            '_raw'            => [],
        ];
    }

    private function reverseMapType(string $csl): int
    {
        return match($csl) {
            'article-journal'  => 17,
            'paper-conference' => 10,
            'book'             => 6,
            'chapter'          => 7,
            'thesis'           => 32,
            'report'           => 27,
            default            => 13,
        };
    }

    private function cslTypeToEndnoteLabel(string $csl): string
    {
        return array_flip(self::TYPE_MAP)[$csl] ?? 'Generic';
    }

    private function extractKeywords(\SimpleXMLElement $r): array
    {
        $keywords = [];
        $keywordElements = $r->keywords->keyword ?? [];
        
        if ($keywordElements instanceof \SimpleXMLElement) {
            foreach ($keywordElements as $kw) {
                $keywords[] = (string) $kw;
            }
        }
        
        return $keywords;
    }
}
