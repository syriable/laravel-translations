<?php

declare(strict_types=1);

namespace Syriable\Translations\Storage;

use Syriable\Translations\Contracts\FileFormat;
use Syriable\Translations\Exceptions\UnsupportedFormatException;

/**
 * Resolves the {@see FileFormat} responsible for a given file extension.
 */
final class FormatRegistry
{
    /**
     * @var array<string, FileFormat>
     */
    private array $formats = [];

    /**
     * @param  iterable<FileFormat>  $formats
     */
    public function __construct(iterable $formats = [])
    {
        foreach ($formats as $format) {
            $this->register($format);
        }
    }

    public function register(FileFormat $format): self
    {
        $this->formats[$format->extension()] = $format;

        return $this;
    }

    public function has(string $extension): bool
    {
        return isset($this->formats[$extension]);
    }

    public function for(string $extension): FileFormat
    {
        return $this->formats[$extension] ?? throw UnsupportedFormatException::forExtension($extension);
    }
}
