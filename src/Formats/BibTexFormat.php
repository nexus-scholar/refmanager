<?php

namespace Nexus\RefManager\Formats;

use Illuminate\Support\Collection;
use RenanBr\BibTexParser\Listener;
use RenanBr\BibTexParser\Parser;
use Nexus\RefManager\Formats\Contracts\ReferenceFormat;
use Nexus\RefManager\Services\AuthorResolver;

class BibTexFormat implements ReferenceFormat
{
    private const TYPE_MAP = [
        'article'       => 'article-journal',
        'inproceedings' => 'paper-conference',
        'conference'    => 'paper-conference',
        'book'          => 'book',
        'incollection'  => 'chapter',
        'phdthesis'     => 'thesis',
        'mastersthesis' => 'thesis',
        'techreport'    => 'report',
        'misc'          => 'article',
    ];

    private const TYPE_MAP_REVERSE = [
        'article-journal'  => 'article',
        'paper-conference' => 'inproceedings',
        'book'             => 'book',
        'chapter'          => 'incollection',
        'thesis'           => 'phdthesis',
        'report'           => 'techreport',
    ];

    public function parse(string $content): Collection
    {
        $parser   = new Parser();
        $listener = new Listener();
        $parser->addListener($listener);
        $parser->parseString($content);

        return collect($listener->export())
            ->filter(fn($e) => ($e['type'] ?? '') !== 'string')
            ->map(fn($entry) => $this->normalize($entry));
    }

    public function serialize(Collection $canonicals): string
    {
        return $canonicals->map(function (array $c): string {
            $type    = self::TYPE_MAP_REVERSE[$c['type'] ?? ''] ?? 'misc';
            $citekey = $this->generateCiteKey($c);
            $fields  = [];

            $fields['title']   = '{' . ($c['title'] ?? '') . '}';
            $fields['author']  = implode(' and ', array_map(
                fn($a) => isset($a['literal'])
                    ? $a['literal']
                    : (($a['family'] ?? '') . ', ' . ($a['given'] ?? '')),
                $c['author'] ?? []
            ));

            $year = $c['issued']['date-parts'][0][0] ?? null;
            if ($year) $fields['year'] = $year;

            if (!empty($c['abstract']))        $fields['abstract']  = '{' . $c['abstract'] . '}';
            if (!empty($c['DOI']))             $fields['doi']       = $c['DOI'];
            if (!empty($c['URL']))             $fields['url']       = $c['URL'];
            if (!empty($c['container-title'])) $fields['journal']   = '{' . $c['container-title'] . '}';
            if (!empty($c['volume']))          $fields['volume']    = $c['volume'];
            if (!empty($c['issue']))           $fields['number']    = $c['issue'];
            if (!empty($c['page']))            $fields['pages']     = str_replace('-', '--', $c['page']);
            if (!empty($c['publisher']))       $fields['publisher'] = '{' . $c['publisher'] . '}';
            if (!empty($c['keyword']))         $fields['keywords']  = implode(', ', $c['keyword']);

            $fieldLines = collect($fields)
                ->map(fn($v, $k) => "  {$k} = {$v}")
                ->join(",\n");

            return "@{$type}{{$citekey},\n{$fieldLines}\n}";
        })->join("\n\n");
    }

    public function extensions(): array  { return ['bib', 'bibtex']; }
    public function mimeTypes(): array   { return ['application/x-bibtex']; }
    public function label(): string      { return 'BibTeX'; }

    private function normalize(array $entry): array
    {
        $authorString = $entry['author'] ?? '';
        $authors = array_map(
            fn($a) => AuthorResolver::parse(trim($a)),
            preg_split('/\s+and\s+/i', $authorString)
        );
        $authors = array_filter($authors, fn($a) => !empty(array_filter($a)));

        $pages = isset($entry['pages'])
            ? preg_replace('/--+/', '-', $entry['pages'])
            : null;

        $year = $entry['year'] ?? null;

        return [
            'type'            => self::TYPE_MAP[strtolower($entry['type'] ?? '')] ?? 'article',
            'title'           => trim($entry['title'] ?? '', '{} '),
            'abstract'        => isset($entry['abstract']) ? trim($entry['abstract'], '{}') : null,
            'DOI'             => $entry['doi'] ?? null,
            'URL'             => $entry['url'] ?? null,
            'container-title' => $entry['journal'] ?? $entry['booktitle'] ?? null,
            'volume'          => $entry['volume'] ?? null,
            'issue'           => $entry['number'] ?? null,
            'page'            => $pages,
            'publisher'       => isset($entry['publisher']) ? trim($entry['publisher'], '{}') : null,
            'publisher-place' => $entry['address'] ?? null,
            'issued'          => $year ? ['date-parts' => [[(int)$year]]] : null,
            'author'          => array_values($authors),
            'keyword'         => isset($entry['keywords'])
                ? array_map('trim', explode(',', $entry['keywords']))
                : [],
            '_raw'            => $entry,
        ];
    }

    private function generateCiteKey(array $canonical): string
    {
        $author = $canonical['author'][0]['family'] ?? 'unknown';
        $year   = $canonical['issued']['date-parts'][0][0] ?? 'nd';
        $word   = strtolower(preg_replace('/[^a-zA-Z]/', '', explode(' ', $canonical['title'] ?? '')[0] ?? ''));
        return strtolower("{$author}{$year}{$word}");
    }
}
