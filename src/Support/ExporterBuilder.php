<?php

namespace Nexus\RefManager\Support;

use Illuminate\Support\Collection;
use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Models\ReferenceCollection;
use Nexus\RefManager\ReferenceExporter;

class ExporterBuilder
{
    public function __construct(
        private readonly ReferenceExporter $exporter,
        private readonly Collection $documents,
    ) {}

    public function asRis(): string
    {
        return $this->exporter->toString($this->documents, 'ris');
    }

    public function asBibtex(): string
    {
        return $this->exporter->toString($this->documents, 'bibtex');
    }

    public function asCslJson(): string
    {
        return $this->exporter->toString($this->documents, 'csl_json');
    }

    public function asEndnoteXml(): string
    {
        return $this->exporter->toString($this->documents, 'endnote_xml');
    }

    public function toResponse(string $format, string $filename): \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->exporter->toResponse($this->documents, $format, $filename);
    }

    public function download(string $format, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->exporter->toResponse($this->documents, $format, $filename);
    }

    public static function documents(iterable $documents): self
    {
        $documents = collect($documents);
        return new self(app(ReferenceExporter::class), $documents);
    }

    public static function collection(ReferenceCollection $collection): self
    {
        return self::documents($collection->documents()->get());
    }

    public static function project(int $projectId): self
    {
        $documentModel = config('refmanager.document_model');
        $documents = $documentModel::where('project_id', $projectId)->get();
        return self::documents($documents);
    }
}
