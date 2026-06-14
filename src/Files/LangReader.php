<?php

namespace Syriable\Translations\Files;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class LangReader
{
    public function locales(string $langPath): array
    {
        if (! is_dir($langPath)) {
            return [];
        }

        $locales = [];

        foreach (glob($langPath.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);

            if ($name !== 'vendor') {
                $locales[] = $name;
            }
        }

        foreach (glob($langPath.'/*.json') ?: [] as $file) {
            $locales[] = Str::beforeLast(basename($file), '.json');
        }

        return array_values(array_unique($locales));
    }

    public function phpFiles(string $langPath, string $locale): array
    {
        $dir = "{$langPath}/{$locale}";

        if (! is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($dir))), '/');
            $files[substr($relative, 0, -4)] = $file->getPathname();
        }

        ksort($files);

        return $files;
    }

    public function vendorFiles(string $langPath): array
    {
        $base = "{$langPath}/vendor";

        if (! is_dir($base)) {
            return [];
        }

        $entries = [];

        foreach (glob($base.'/*', GLOB_ONLYDIR) ?: [] as $namespaceDir) {
            $namespace = basename($namespaceDir);

            foreach (glob($namespaceDir.'/*', GLOB_ONLYDIR) ?: [] as $localeDir) {
                $locale = basename($localeDir);

                foreach (glob($localeDir.'/*.php') ?: [] as $file) {
                    $entries[] = [
                        'namespace' => $namespace,
                        'locale' => $locale,
                        'group' => basename($file, '.php'),
                        'path' => $file,
                    ];
                }
            }
        }

        return $entries;
    }

    public function readPhp(string $path): array
    {
        $data = require $path;

        return is_array($data) ? Arr::dot($data) : [];
    }

    public function readJson(string $path): array
    {
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    public function jsonPath(string $langPath, string $locale): string
    {
        return "{$langPath}/{$locale}.json";
    }
}
