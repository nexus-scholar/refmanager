<?php

namespace Nexus\RefManager\Services;

class AuthorResolver
{
    /**
     * Parse a raw author string into a canonical author array.
     * Handles: "Smith, John A." | "John A. Smith" | "{IEEE Task Force}"
     *
     * @return array{family?: string, given?: string, literal?: string, ORCID?: null}
     */
    public static function parse(string $raw): array
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            return ['literal' => trim($raw, '{}'), 'ORCID' => null];
        }

        if (str_contains($raw, ',')) {
            [$last, $first] = array_map('trim', explode(',', $raw, 2));
            return ['family' => $last, 'given' => $first, 'ORCID' => null];
        }

        $parts = preg_split('/\s+/', $raw);
        if (count($parts) >= 2) {
            $family = array_pop($parts);
            return ['family' => $family, 'given' => implode(' ', $parts), 'ORCID' => null];
        }

        return ['family' => $raw, 'given' => '', 'ORCID' => null];
    }

    /**
     * Resolve or create an Author Eloquent model from canonical author array.
     */
    public function resolve(array $authorData): mixed
    {
        $authorModel = config('refmanager.author_model');

        if (isset($authorData['literal'])) {
            return $authorModel::firstOrCreate(
                ['family_name' => $authorData['literal'], 'given_name' => ''],
            );
        }

        return $authorModel::firstOrCreate([
            'family_name' => $authorData['family'] ?? '',
            'given_name'  => $authorData['given'] ?? '',
        ]);
    }
}
