<?php

namespace Syriable\Translations\Scanning;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileWalker
{
    public function __construct(
        private readonly string $basePath,
    ) {}

    /** @return iterable<SplFileInfo> */
    public function walk(array $paths, array $extensions): iterable
    {
        foreach ($paths as $path) {
            $absolute = $this->absolute($path);

            if (is_file($absolute)) {
                yield new SplFileInfo($absolute);

                continue;
            }

            if (! is_dir($absolute)) {
                continue;
            }

            yield from $this->walkDirectory($absolute, $extensions);
        }
    }

    private function walkDirectory(string $directory, array $extensions): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $this->matches($file->getFilename(), $extensions)) {
                yield $file;
            }
        }
    }

    private function matches(string $filename, array $extensions): bool
    {
        foreach ($extensions as $extension) {
            if (str_ends_with($filename, '.'.$extension)) {
                return true;
            }
        }

        return false;
    }

    public function relative(string $path): string
    {
        return str_starts_with($path, $this->basePath)
            ? ltrim(substr($path, strlen($this->basePath)), '/\\')
            : $path;
    }

    private function absolute(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return rtrim($this->basePath, '/\\').'/'.$path;
    }
}
