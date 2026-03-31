<?php

namespace Nexus\RefManager\Models;

use Illuminate\Support\Collection;

final class ImportResult
{
    public function __construct(
        /** All parsed documents (including duplicates) */
        public readonly Collection $documents,

        /** Net-new documents (saved or ready to save) */
        public readonly Collection $imported,

        /** Records identified as duplicates */
        public readonly Collection $duplicates,

        /** Records that failed to parse */
        public readonly Collection $failed,

        /** Persisted audit log entry */
        public readonly ?ImportLog $log = null,
    ) {}

    public function total(): int
    {
        return $this->documents->count();
    }

    public function count(): int
    {
        return $this->imported->count();
    }

    public function wasSuccessful(): bool
    {
        return $this->failed->isEmpty();
    }
}
