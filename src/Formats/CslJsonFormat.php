<?php

namespace Nexus\RefManager\Formats;

use Illuminate\Support\Collection;
use Nexus\RefManager\Exceptions\ParseException;
use Nexus\RefManager\Formats\Contracts\ReferenceFormat;

class CslJsonFormat implements ReferenceFormat
{
    public function parse(string $content): Collection
    {
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParseException('Invalid JSON: ' . json_last_error_msg(), 'csl_json', $content);
        }

        // Handle both a bare array and a {"items": [...]} envelope
        $items = isset($decoded['items']) ? $decoded['items'] : $decoded;

        if (!is_array($items)) {
            throw new ParseException('CSL-JSON must be an array or have an "items" key.', 'csl_json');
        }

        return collect($items)->map(fn($item) => $this->normalize($item));
    }

    public function serialize(Collection $canonicals): string
    {
        return json_encode(
            $canonicals->values()->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function extensions(): array  { return ['json']; }
    public function mimeTypes(): array   { return ['application/vnd.citationstyles.csl+json', 'application/json']; }
    public function label(): string      { return 'CSL-JSON'; }

    private function normalize(array $item): array
    {
        // CSL-JSON IS the canonical format — strip unknown keys into _raw
        $known = ['type','title','abstract','DOI','URL','language','source',
                  'container-title','volume','issue','page','publisher',
                  'publisher-place','issued','author','editor','keyword',
                  'ISSN','ISBN','PMID','PMCID','note','annote'];

        $raw = array_diff_key($item, array_flip($known));

        return array_merge(
            array_intersect_key($item, array_flip($known)),
            ['_raw' => $raw]
        );
    }
}
