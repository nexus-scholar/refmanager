<?php

namespace Nexus\RefManager\Formats;

use Illuminate\Support\Collection;
use Nexus\RefManager\Exceptions\ParseException;
use Nexus\RefManager\Formats\Contracts\ReferenceFormat;

class VectorJsonlFormat implements ReferenceFormat
{
    public function parse(string $content): Collection
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];

        return collect($lines)
            ->filter(fn (string $line) => trim($line) !== '')
            ->map(function (string $line, int $index): array {
                $decoded = json_decode($line, true);
                if (!is_array($decoded)) {
                    throw new ParseException(
                        message: sprintf('Invalid JSONL line at index %d', $index),
                        format: 'vector_jsonl',
                        rawRecord: $line,
                    );
                }

                return [
                    'type' => $decoded['type'] ?? 'article-journal',
                    'title' => $decoded['title'] ?? '',
                    'abstract' => $decoded['abstract'] ?? null,
                    'DOI' => $decoded['doi'] ?? null,
                    'URL' => $decoded['url'] ?? null,
                    'issued' => isset($decoded['year']) ? ['date-parts' => [[(int) $decoded['year']]]] : null,
                    'keyword' => $decoded['keywords'] ?? [],
                    'author' => collect($decoded['authors'] ?? [])
                        ->filter(fn ($author) => is_array($author))
                        ->map(fn (array $author) => [
                            'family' => trim((string) ($author['family_name'] ?? $author['family'] ?? '')),
                            'given' => trim((string) ($author['given_name'] ?? $author['given'] ?? '')),
                        ])
                        ->filter(fn (array $author) => $author['family'] !== '' || $author['given'] !== '')
                        ->values()
                        ->all(),
                    '_raw' => $decoded,
                ];
            })
            ->values();
    }

    public function serialize(Collection $canonicals): string
    {
        return $canonicals
            ->map(function (array $item): string {
                $title = trim((string) ($item['title'] ?? ''));
                $abstract = trim((string) ($item['abstract'] ?? ''));
                $text = trim($title."\n\n".$abstract);

                return json_encode([
                    'type' => $item['type'] ?? 'article-journal',
                    'title' => $title,
                    'abstract' => $abstract !== '' ? $abstract : null,
                    'doi' => $item['DOI'] ?? null,
                    'url' => $item['URL'] ?? null,
                    'year' => $item['issued']['date-parts'][0][0] ?? null,
                    'keywords' => array_values($item['keyword'] ?? []),
                    'text' => $text,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            })
            ->implode("\n");
    }

    public function extensions(): array
    {
        return ['jsonl', 'ndjson'];
    }

    public function mimeTypes(): array
    {
        return ['application/x-ndjson', 'application/jsonl'];
    }

    public function label(): string
    {
        return 'Vector JSONL';
    }
}

