<?php

namespace Nexus\RefManager;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferenceExporter
{
    public function __construct(
        private readonly FormatManager $formatManager,
    ) {}

    public function toString(Collection $documents, string $formatName): string
    {
        $format     = $this->formatManager->byName($formatName);
        $canonicals = $documents->map(fn($doc) => $doc->toCanonical());
        return $format->serialize($canonicals);
    }

    public function toResponse(Collection $documents, string $formatName, string $filename): StreamedResponse
    {
        $content  = $this->toString($documents, $formatName);
        $format   = $this->formatManager->byName($formatName);
        $mimeType = $format->mimeTypes()[0] ?? 'application/octet-stream';

        return response()->streamDownload(
            fn () => print($content),
            $filename,
            ['Content-Type' => $mimeType],
        );
    }

    public function fromCollection(mixed $collection, string $formatName): StreamedResponse
    {
        $ext = match($formatName) {
            'bibtex'      => 'bib',
            'csl_json'    => 'json',
            'endnote_xml' => 'xml',
            default       => 'ris',
        };
        return $this->toResponse(
            $collection->documents()->with('authors')->get(),
            $formatName,
            "{$collection->name}.{$ext}",
        );
    }
}
