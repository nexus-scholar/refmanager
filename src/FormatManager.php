<?php

namespace Nexus\RefManager;

use Illuminate\Http\UploadedFile;
use Nexus\RefManager\Exceptions\UnsupportedFormatException;
use Nexus\RefManager\Formats\Contracts\ReferenceFormat;

class FormatManager
{
    /** @var array<string, class-string<ReferenceFormat>> */
    private array $formats = [];

    public function register(string $name, string $formatClass): void
    {
        $this->formats[$name] = $formatClass;
    }

    public function byName(string $name): ReferenceFormat
    {
        $class = $this->formats[$name]
            ?? throw new UnsupportedFormatException($name);

        return app($class);
    }

    public function byExtension(string $ext): ReferenceFormat
    {
        $ext = strtolower(ltrim($ext, '.'));

        foreach ($this->formats as $name => $class) {
            $instance = app($class);
            if (in_array($ext, $instance->extensions(), true)) {
                return $instance;
            }
        }

        throw new UnsupportedFormatException($ext);
    }

    public function byMime(string $mime): ReferenceFormat
    {
        foreach ($this->formats as $class) {
            $instance = app($class);
            if (in_array($mime, $instance->mimeTypes(), true)) {
                return $instance;
            }
        }

        throw new UnsupportedFormatException($mime);
    }

    public function fromUpload(UploadedFile $file): ReferenceFormat
    {
        $ext = strtolower($file->getClientOriginalExtension());
        return $this->byExtension($ext);
    }

    /** @return array<string, class-string<ReferenceFormat>> */
    public function all(): array
    {
        return $this->formats;
    }
}
