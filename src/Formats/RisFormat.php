<?php

namespace Nexus\RefManager\Formats;

use Illuminate\Support\Collection;
use Nexus\RefManager\Formats\Contracts\ReferenceFormat;
use Nexus\RefManager\Services\AuthorResolver;

class RisFormat implements ReferenceFormat
{
    private const TYPE_MAP = [
        'JOUR' => 'article-journal',
        'CONF' => 'paper-conference',
        'CPAPER' => 'paper-conference',
        'BOOK' => 'book',
        'CHAP' => 'chapter',
        'THES' => 'thesis',
        'RPRT' => 'report',
        'ELEC' => 'webpage',
        'GEN'  => 'article',
        'ABST' => 'article',
        'MGZN' => 'article-magazine',
    ];

    private const TYPE_MAP_REVERSE = [
        'article-journal'   => 'JOUR',
        'paper-conference'  => 'CONF',
        'book'              => 'BOOK',
        'chapter'           => 'CHAP',
        'thesis'            => 'THES',
        'report'            => 'RPRT',
        'webpage'           => 'ELEC',
    ];

    public function parse(string $content): Collection
    {
        $records = [];
        $current = [];
        $lastTag = null;

        foreach (explode("\n", $content) as $line) {
            $line = rtrim($line, "\r");

            if (str_starts_with($line, 'ER')) {
                if (!empty($current)) {
                    $records[] = $this->normalize($current);
                    $current   = [];
                    $lastTag   = null;
                }
                continue;
            }

            if (preg_match('/^([A-Z][A-Z0-9])\s{2}-\s(.*)$/', $line, $m)) {
                $tag     = $m[1];
                $value   = trim($m[2]);
                $lastTag = $tag;

                if (in_array($tag, ['AU', 'A1', 'A2', 'KW', 'N1'])) {
                    $current[$tag][] = $value;
                } else {
                    $current[$tag] = $value;
                }
            } elseif ($lastTag && !empty(trim($line))) {
                if (is_array($current[$lastTag])) {
                    $idx = array_key_last($current[$lastTag]);
                    $current[$lastTag][$idx] .= ' ' . trim($line);
                } else {
                    $current[$lastTag] .= ' ' . trim($line);
                }
            }
        }

        return collect($records);
    }

    public function serialize(Collection $canonicals): string
    {
        return $canonicals->map(function (array $c): string {
            $lines = [];
            $type  = self::TYPE_MAP_REVERSE[$c['type'] ?? ''] ?? 'GEN';

            $lines[] = "TY  - {$type}";
            $lines[] = "TI  - " . ($c['title'] ?? '');

            foreach ($c['author'] ?? [] as $author) {
                $name = isset($author['literal'])
                    ? $author['literal']
                    : (($author['family'] ?? '') . ', ' . ($author['given'] ?? ''));
                $lines[] = "AU  - " . trim($name, ', ');
            }

            if (!empty($c['abstract']))        $lines[] = "AB  - {$c['abstract']}";
            if (!empty($c['DOI']))              $lines[] = "DO  - {$c['DOI']}";
            if (!empty($c['URL']))              $lines[] = "UR  - {$c['URL']}";
            if (!empty($c['container-title']))  $lines[] = "JO  - {$c['container-title']}";
            if (!empty($c['volume']))           $lines[] = "VL  - {$c['volume']}";
            if (!empty($c['issue']))            $lines[] = "IS  - {$c['issue']}";
            if (!empty($c['publisher']))        $lines[] = "PB  - {$c['publisher']}";
            if (!empty($c['language']))         $lines[] = "LA  - {$c['language']}";

            if (!empty($c['page'])) {
                [$sp, $ep] = array_pad(explode('-', $c['page'], 2), 2, null);
                $lines[]   = "SP  - {$sp}";
                if ($ep) $lines[] = "EP  - {$ep}";
            }

            $year = $c['issued']['date-parts'][0][0] ?? null;
            if ($year) $lines[] = "PY  - {$year}";

            foreach ($c['keyword'] ?? [] as $kw) {
                $lines[] = "KW  - {$kw}";
            }

            $lines[] = "ER  - ";
            return implode("\n", $lines);
        })->join("\n\n");
    }

    public function extensions(): array  { return ['ris']; }
    public function mimeTypes(): array   { return ['application/x-research-info-systems']; }
    public function label(): string      { return 'RIS'; }

    private function normalize(array $ris): array
    {
        $authors = array_map(
            fn($a) => AuthorResolver::parse($a),
            $ris['AU'] ?? $ris['A1'] ?? []
        );

        $sp    = $ris['SP'] ?? null;
        $ep    = $ris['EP'] ?? null;
        $pages = $sp && $ep ? "{$sp}-{$ep}" : $sp;

        $year  = $ris['PY'] ?? $ris['Y1'] ?? null;
        if ($year && str_contains((string)$year, '/')) {
            $year = explode('/', $year)[0];
        }

        return [
            'type'            => self::TYPE_MAP[$ris['TY'] ?? 'GEN'] ?? 'article',
            'title'           => $ris['TI'] ?? $ris['T1'] ?? '',
            'abstract'        => $ris['AB'] ?? $ris['N2'] ?? null,
            'DOI'             => $ris['DO'] ?? null,
            'URL'             => $ris['UR'] ?? null,
            'container-title' => $ris['JO'] ?? $ris['JF'] ?? $ris['T2'] ?? null,
            'volume'          => $ris['VL'] ?? null,
            'issue'           => $ris['IS'] ?? null,
            'page'            => $pages,
            'publisher'       => $ris['PB'] ?? null,
            'publisher-place' => $ris['CY'] ?? null,
            'language'        => $ris['LA'] ?? null,
            'issued'          => $year ? ['date-parts' => [[(int)$year]]] : null,
            'author'          => $authors,
            'keyword'         => $ris['KW'] ?? [],
            '_raw'            => $ris,
        ];
    }

    private function mapType(string $ty): string
    {
        return self::TYPE_MAP[$ty] ?? 'article';
    }
}
