<?php

declare(strict_types=1);

namespace Syriable\Translations\Detection;

use Syriable\Translations\Contracts\HardcodedScanner;
use Syriable\Translations\Support\FileFinder;

/**
 * Walks the configured source paths and runs each file through the scanner that
 * owns its extension, aggregating every hardcoded string found.
 */
final class HardcodedStringDetector
{
    /**
     * @param  list<HardcodedScanner>  $scanners
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
     * @return list<DetectedString>
     */
    public function detect(array $paths): array
    {
        $found = [];

        foreach ($this->finder->find($paths, $this->extensions(), $this->excludePaths, $this->basePath) as $file) {
            $scanner = $this->scannerFor($file['relativePath']);

            if ($scanner === null) {
                continue;
            }

            $contents = @file_get_contents($file['absolutePath']);

            if ($contents === false) {
                continue;
            }

            foreach ($scanner->scan($contents, $file['relativePath']) as $detected) {
                $found[] = $detected;
            }
        }

        return $found;
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

    private function scannerFor(string $relativePath): ?HardcodedScanner
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
