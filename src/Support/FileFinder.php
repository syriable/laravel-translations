<?php

declare(strict_types=1);

namespace Syriable\Translations\Support;

use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Walks a set of paths and yields the files that match the requested
 * extensions, skipping any excluded directory segment. Yields lazily so large
 * codebases stream rather than load into memory.
 */
final class FileFinder
{
    /**
     * @param  list<string>  $paths
     * @param  list<string>  $extensions  longest extensions (e.g. "blade.php") are matched first
     * @param  list<string>  $exclude  directory segments to skip
     * @return Generator<int, array{absolutePath: string, relativePath: string}>
     */
    public function find(array $paths, array $extensions, array $exclude, string $basePath): Generator
    {
        $extensions = $this->sortByLength($extensions);

        foreach ($paths as $path) {
            if (is_file($path)) {
                if ($this->matches($path, $extensions) && ! $this->isExcluded($path, $exclude)) {
                    yield $this->entry($path, $basePath);
                }

                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            yield from $this->walk($path, $extensions, $exclude, $basePath);
        }
    }

    /**
     * @param  list<string>  $extensions
     * @param  list<string>  $exclude
     * @return Generator<int, array{absolutePath: string, relativePath: string}>
     */
    private function walk(string $path, array $extensions, array $exclude, string $basePath): Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $absolute = $file->getPathname();

            if ($this->isExcluded($absolute, $exclude) || ! $this->matches($absolute, $extensions)) {
                continue;
            }

            yield $this->entry($absolute, $basePath);
        }
    }

    /**
     * @return array{absolutePath: string, relativePath: string}
     */
    private function entry(string $absolute, string $basePath): array
    {
        return [
            'absolutePath' => $absolute,
            'relativePath' => $this->relativePath($absolute, $basePath),
        ];
    }

    /**
     * @param  list<string>  $extensions
     */
    private function matches(string $path, array $extensions): bool
    {
        foreach ($extensions as $extension) {
            if (str_ends_with($path, '.'.$extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $exclude
     */
    private function isExcluded(string $path, array $exclude): bool
    {
        $normalized = str_replace('\\', '/', $path);

        foreach ($exclude as $segment) {
            if (str_contains($normalized, '/'.trim($segment, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $absolute, string $basePath): string
    {
        $base = rtrim($basePath, '/\\').'/';

        if (str_starts_with($absolute, $base)) {
            return substr($absolute, strlen($base));
        }

        return $absolute;
    }

    /**
     * @param  list<string>  $extensions
     * @return list<string>
     */
    private function sortByLength(array $extensions): array
    {
        usort($extensions, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $extensions;
    }
}
