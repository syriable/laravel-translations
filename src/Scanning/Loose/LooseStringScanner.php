<?php

namespace Syriable\Translations\Scanning\Loose;

use SplFileInfo;
use Syriable\Translations\Enums\LooseStringStatus;
use Syriable\Translations\Models\IgnoredString;
use Syriable\Translations\Models\LooseString;
use Syriable\Translations\Scanning\FileWalker;

class LooseStringScanner
{
    public function __construct(
        private readonly FalsePositiveFilter $filter = new FalsePositiveFilter,
    ) {}

    public function scan(?string $path = null, ?string $basePath = null): array
    {
        $basePath = $basePath ?? base_path();
        $walker = new FileWalker($basePath);
        $paths = $path ? [$path] : config('translations.scanning.paths', []);
        $extensions = config('translations.scanning.extensions', []);

        $ignored = $this->ignoredHashes();
        $detected = 0;
        $files = 0;
        $seen = [];

        foreach ($walker->walk($paths, $extensions) as $file) {
            $files++;
            $relative = $walker->relative($file->getPathname());
            $contents = (string) file_get_contents($file->getPathname());

            foreach ($this->extract($file, $contents) as $match) {
                $hash = hash('sha256', $match['text']);

                if ($this->isIgnored($ignored, $relative, $hash)) {
                    continue;
                }

                $record = LooseString::query()->updateOrCreate(
                    ['file_path' => $relative, 'line' => $match['line'], 'text_hash' => $hash],
                    [
                        'text' => $match['text'],
                        'element_type' => $match['element_type'],
                        'scanner' => $this->scannerType($file),
                        'status' => LooseStringStatus::Pending,
                    ],
                );

                $seen[] = $record->id;
                $detected++;
            }
        }

        $resolved = LooseString::query()
            ->where('status', LooseStringStatus::Pending->value)
            ->whereNotIn('id', $seen ?: [0])
            ->update(['status' => LooseStringStatus::Resolved->value]);

        return ['files_scanned' => $files, 'detected' => $detected, 'resolved' => $resolved];
    }

    private function scannerType(SplFileInfo $file): string
    {
        $filename = $file->getFilename();

        return match (true) {
            str_ends_with($filename, '.blade.php') => 'blade',
            str_ends_with($filename, '.vue') => 'vue',
            str_ends_with($filename, '.jsx'), str_ends_with($filename, '.tsx') => 'react',
            default => $file->getExtension() ?: 'php',
        };
    }

    private function extract(SplFileInfo $file, string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents);
        $matches = [];

        foreach ($lines as $number => $line) {
            if (preg_match('/\b(?:__|trans|trans_choice|@lang|\bt)\(/', $line)) {
                continue;
            }

            foreach ($this->candidates($line) as [$text, $type]) {
                if ($this->filter->rejects($text)) {
                    continue;
                }

                $matches[] = ['text' => trim($text), 'line' => $number + 1, 'element_type' => $type];
            }
        }

        return $matches;
    }

    private function candidates(string $line): array
    {
        $candidates = [];

        if (preg_match_all('/>([^<>{}]+)</', $line, $textNodes)) {
            foreach ($textNodes[1] as $text) {
                $candidates[] = [$text, 'text'];
            }
        }

        if (preg_match_all('/\b(?:title|placeholder|alt|label|aria-label)\s*=\s*"([^"]+)"/', $line, $attributes)) {
            foreach ($attributes[1] as $text) {
                $candidates[] = [$text, 'attribute'];
            }
        }

        return $candidates;
    }

    private function ignoredHashes(): array
    {
        return IgnoredString::query()->get(['file_path', 'text_hash', 'scope'])->all();
    }

    private function isIgnored(array $ignored, string $path, string $hash): bool
    {
        foreach ($ignored as $rule) {
            if ($rule->text_hash !== $hash) {
                continue;
            }

            if ($rule->scope === 'global' || $rule->file_path === $path) {
                return true;
            }
        }

        return false;
    }
}
