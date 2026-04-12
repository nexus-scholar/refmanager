<?php

namespace Nexus\RefManager\Support;

final class TitleNormalizer
{
    public static function normalize(string $title): string
    {
        $title = strtolower(trim($title));

        return preg_replace('/\s+/u', ' ', $title) ?? $title;
    }
}

