<?php

declare(strict_types=1);

namespace Syriable\Translations\Extraction;

use Syriable\Translations\Contracts\Scanner;
use Syriable\Translations\Domain\ExtractedKey;
use Syriable\Translations\Support\FileFinder;

/**
 * Orchestrates extraction: finds candidate files, dispatches each to the
 * scanner that owns its extension, and aggregates the discovered keys.
 */
final class Extractor
{
    /**
     * @param  list<Scanner>  $scanners
     * @param  list<string>  $excludePaths
     */
    public function __construct(
        private readonly FileFinder $finder,
        private readonly array $scanners,
        private readonly array $excludePaths,
        private readonly string $basePath,
    ) {}

    /**
     * @param  list<string>  $paths
     */
    public function extract(array $paths): ExtractionResult
    {
        $extensions = $this->extensions();

        /** @var array<string, ExtractedKey> $aggregate */
        $aggregate = [];

        foreach ($this->finder->find($paths, $extensions, $this->excludePaths, $this->basePath) as $file) {
            $scanner = $this->scannerFor($file['relativePath']);

            if ($scanner === null) {
                continue;
            }

            $contents = @file_get_contents($file['absolutePath']);

            if ($contents === false) {
                continue;
            }

            foreach ($scanner->scan($contents, $file['relativePath']) as $extracted) {
                $key = $extracted->key->value;

                $aggregate[$key] = isset($aggregate[$key])
                    ? $aggregate[$key]->mergedWith($extracted)
                    : $extracted;
            }
        }

        return new ExtractionResult($aggregate);
    }

    /**
     * @return list<string>
     */
    private function extensions(): array
    {
        $extensions = [];

        foreach ($this->scanners as $scanner) {
            foreach ($scanner->extensions() as $extension) {
                $extensions[$extension] = true;
            }
        }

        return array_keys($extensions);
    }

    private function scannerFor(string $relativePath): ?Scanner
    {
        $match = null;
        $matchLength = -1;

        foreach ($this->scanners as $scanner) {
            foreach ($scanner->extensions() as $extension) {
                if (str_ends_with($relativePath, '.'.$extension) && strlen($extension) > $matchLength) {
                    $match = $scanner;
                    $matchLength = strlen($extension);
                }
            }
        }

        return $match;
    }
}
