<?php

namespace Nexus\RefManager\Formats\Contracts;

use Illuminate\Support\Collection;

interface ReferenceFormat
{
    /**
     * Parse raw file content into a Collection of canonical CSL-JSON arrays.
     *
     * @throws \Nexus\RefManager\Exceptions\ParseException
     */
    public function parse(string $content): Collection;

    /**
     * Serialize a Collection of canonical arrays to the format's raw string.
     */
    public function serialize(Collection $canonicals): string;

    /**
     * File extensions handled (lowercase, no dot).
     *
     * @return string[]
     */
    public function extensions(): array;

    /**
     * MIME types for this format.
     *
     * @return string[]
     */
    public function mimeTypes(): array;

    /**
     * Human-readable label.
     */
    public function label(): string;
}
