<?php

declare(strict_types=1);

namespace Syriable\Translations\Extraction;

use Syriable\Translations\Models\KeyContext;

/**
 * Persists where each translation key is used in source code, derived from an
 * extraction run, so the management UI can show the call sites for a key.
 *
 * A scan is a full refresh: existing contexts are replaced with the current
 * extraction result.
 */
final class KeyContextRecorder
{
    public function enabled(): bool
    {
        return config('translations.metadata.enabled', true) === true;
    }

    /**
     * Replace all stored contexts with those discovered in the extraction.
     * Returns the number of contexts recorded.
     */
    public function sync(ExtractionResult $result): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return (new KeyContext)->getConnection()->transaction(function () use ($result): int {
            KeyContext::query()->delete();

            $recorded = 0;

            foreach ($result->all() as $extracted) {
                $key = $extracted->key->value;
                $hash = KeyContext::hashKey($key);

                foreach ($extracted->references as $reference) {
                    KeyContext::query()->create([
                        'translation_key' => $key,
                        'key_hash' => $hash,
                        'file_path' => $reference->path,
                        'line_number' => $reference->line,
                        'helper' => $reference->function,
                        'file_type' => $this->fileType($reference->path),
                    ]);

                    $recorded++;
                }
            }

            return $recorded;
        });
    }

    private function fileType(string $path): string
    {
        return str_ends_with($path, '.blade.php') ? 'blade' : 'php';
    }
}
