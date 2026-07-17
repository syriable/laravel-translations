<?php

namespace Syriable\Translations\Scanning;

use SplFileInfo;

/**
 * Single source of truth for classifying a scanned file as blade, vue,
 * react, php, … — so `welcome.blade.php` is always labelled `blade` and
 * never its bare `php` extension. Used by both the loose-string scanner
 * (the `scanner` column) and the usage scanner (the `file_type` column).
 */
class FileType
{
    public static function forFile(SplFileInfo $file): string
    {
        return self::forPath($file->getPathname());
    }

    public static function forPath(string $path): string
    {
        return match (true) {
            str_ends_with($path, '.blade.php') => 'blade',
            str_ends_with($path, '.vue') => 'vue',
            str_ends_with($path, '.jsx'), str_ends_with($path, '.tsx') => 'react',
            default => pathinfo($path, PATHINFO_EXTENSION) ?: 'php',
        };
    }
}
