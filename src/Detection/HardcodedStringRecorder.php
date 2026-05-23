<?php

declare(strict_types=1);

namespace Syriable\Translations\Detection;

use Syriable\Translations\Domain\Enums\HardcodedStatus;
use Syriable\Translations\Models\HardcodedIgnore;
use Syriable\Translations\Models\HardcodedString;

/**
 * Persists detected hardcoded strings. Each run refreshes the pending list:
 * ignored strings are skipped and already-converted entries are preserved, so
 * triage decisions survive re-scans.
 */
final class HardcodedStringRecorder
{
    public function enabled(): bool
    {
        return config('translations.metadata.enabled', true) === true;
    }

    /**
     * @param  list<DetectedString>  $found
     */
    public function sync(array $found): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return (new HardcodedString)->getConnection()->transaction(function () use ($found): int {
            $ignored = array_flip(
                HardcodedIgnore::query()->whereNotNull('text_hash')->pluck('text_hash')->all(),
            );

            HardcodedString::query()->where('status', HardcodedStatus::Pending)->delete();

            $recorded = 0;

            foreach ($found as $detected) {
                $hash = $detected->hash();

                if (isset($ignored[$hash]) || $this->alreadyConverted($detected, $hash)) {
                    continue;
                }

                HardcodedString::query()->updateOrCreate(
                    [
                        'file_path' => $detected->path,
                        'line_number' => $detected->line,
                        'text_hash' => $hash,
                    ],
                    [
                        'text' => $detected->text,
                        'element_type' => $detected->elementType,
                        'scanner_type' => $detected->scannerType,
                        'status' => HardcodedStatus::Pending,
                    ],
                );

                $recorded++;
            }

            return $recorded;
        });
    }

    private function alreadyConverted(DetectedString $detected, string $hash): bool
    {
        return HardcodedString::query()
            ->where('file_path', $detected->path)
            ->where('line_number', $detected->line)
            ->where('text_hash', $hash)
            ->where('status', HardcodedStatus::Converted)
            ->exists();
    }
}
