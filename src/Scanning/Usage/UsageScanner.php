<?php

namespace Syriable\Translations\Scanning\Usage;

use Syriable\Translations\Models\Bundle;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Models\PhraseUsage;
use Syriable\Translations\Scanning\FileWalker;

class UsageScanner
{
    private const PATTERNS = [
        '/__\(\s*[\'"]([^\'"]+)[\'"]/',
        '/\btrans(?:_choice)?\(\s*[\'"]([^\'"]+)[\'"]/',
        '/@lang\(\s*[\'"]([^\'"]+)[\'"]/',
        '/\bt\(\s*[\'"]([^\'"]+)[\'"]/',
    ];

    public function scan(?string $path = null, ?string $basePath = null): array
    {
        $basePath = $basePath ?? base_path();
        $walker = new FileWalker($basePath);
        $paths = $path ? [$path] : config('translations.scanning.paths', []);
        $extensions = config('translations.scanning.extensions', []);

        $index = $this->phraseIndex();
        $seen = [];
        $files = 0;
        $found = 0;

        foreach ($walker->walk($paths, $extensions) as $file) {
            $files++;
            $contents = (string) file_get_contents($file->getPathname());
            $relative = $walker->relative($file->getPathname());

            foreach ($this->matches($contents) as [$key, $line, $snippet]) {
                $phraseId = $index[$key] ?? null;

                if ($phraseId === null) {
                    continue;
                }

                $found++;
                $usage = PhraseUsage::query()->updateOrCreate(
                    ['phrase_id' => $phraseId, 'file_path' => $relative, 'line' => $line],
                    ['snippet' => $snippet, 'file_type' => $file->getExtension()],
                );

                $seen[] = $usage->id;
            }
        }

        $removed = PhraseUsage::query()->whereNotIn('id', $seen ?: [0])->delete();

        return ['files_scanned' => $files, 'usages_found' => $found, 'usages_removed' => $removed];
    }

    private function matches(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents);
        $results = [];

        foreach ($lines as $number => $line) {
            foreach (self::PATTERNS as $pattern) {
                if (preg_match_all($pattern, $line, $matches)) {
                    foreach ($matches[1] as $key) {
                        $results[] = [$key, $number + 1, trim($line)];
                    }
                }
            }
        }

        return $results;
    }

    private function phraseIndex(): array
    {
        $index = [];
        $bundles = Bundle::query()->pluck('name', 'id');

        Phrase::query()->select(['id', 'bundle_id', 'key'])->chunkById(1000, function ($phrases) use (&$index, $bundles): void {
            foreach ($phrases as $phrase) {
                $bundle = $bundles[$phrase->bundle_id] ?? null;
                $dotted = $bundle === '_json' ? $phrase->key : "{$bundle}.{$phrase->key}";
                $index[$dotted] = $phrase->id;
                $index[$phrase->key] = $phrase->id;
            }
        });

        return $index;
    }
}
