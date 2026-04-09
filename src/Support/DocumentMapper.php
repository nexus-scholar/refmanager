<?php

namespace Nexus\RefManager\Support;

use InvalidArgumentException;
use RuntimeException;

class DocumentMapper
{
    public static function fromNexus(object $doc): array
    {
        self::assertNexusDocument($doc);

        return [
            'title'          => $doc->title,
            'abstract'       => $doc->abstract,
            'year'           => $doc->year,
            'url'            => $doc->url,
            'language'       => $doc->language,
            'journal'        => $doc->venue,
            'doi'            => $doc->externalIds?->doi,
            'arxiv_id'       => $doc->externalIds?->arxivId,
            'openalex_id'    => $doc->externalIds?->openalexId,
            'pubmed_id'      => $doc->externalIds?->pubmedId,
            's2_id'          => $doc->externalIds?->s2Id,
            'provider'       => $doc->provider ?? 'unknown',
            'provider_id'    => $doc->providerId,
            'cited_by_count' => $doc->citedByCount,
            'query_id'       => $doc->queryId,
            'query_text'     => $doc->queryText,
            'retrieved_at'   => $doc->retrievedAt,
            'cluster_id'     => $doc->clusterId,
            'raw_data'       => $doc->rawData,
        ];
    }

    public static function authorsFromNexus(object $doc): array
    {
        self::assertNexusDocument($doc);

        return array_map(fn ($author) => [
            'family_name' => $author->familyName,
            'given_name'  => $author->givenName,
            'orcid'       => $author->orcid,
        ], $doc->authors ?? []);
    }

    private static function assertNexusDocument(object $doc): void
    {
        if (!class_exists('\\Nexus\\Models\\Document')) {
            throw new RuntimeException('nexus/nexus-php is required to use DocumentMapper::fromNexus().');
        }

        if (!($doc instanceof \Nexus\Models\Document)) {
            throw new InvalidArgumentException('DocumentMapper expects an instance of Nexus\\Models\\Document.');
        }
    }
}

