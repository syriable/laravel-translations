<?php

declare(strict_types=1);

namespace Syriable\Translations\Analysis;

use Illuminate\Support\Str;
use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Extraction\ExtractionResult;

/**
 * Compares discovered key usages with the stored catalog to surface missing
 * keys, unused (dead) keys and per-locale completeness.
 */
final class HealthAnalyzer
{
    /**
     * @param  list<string>  $ignore  wildcard patterns for keys never reported as unused
     */
    public function __construct(private readonly array $ignore = []) {}

    public function analyze(ExtractionResult $extraction, Catalog $catalog): HealthReport
    {
        $source = $catalog->source();
        $sourceKeys = $source?->keys() ?? [];
        $usedKeys = $extraction->keyStrings();

        $missing = array_values(array_diff($usedKeys, $sourceKeys));

        $unused = array_values(array_filter(
            array_diff($sourceKeys, $usedKeys),
            fn (string $key): bool => ! $this->isIgnored($key),
        ));

        $completeness = [];

        foreach ($catalog->all() as $code => $localeCatalog) {
            $completeness[$code] = $this->completenessFor($localeCatalog, $sourceKeys);
        }

        return new HealthReport($missing, $unused, $completeness);
    }

    /**
     * @param  list<string>  $sourceKeys
     */
    private function completenessFor(LocaleCatalog $catalog, array $sourceKeys): CompletenessReport
    {
        $translated = 0;
        $missing = [];

        foreach ($sourceKeys as $key) {
            $value = $catalog->get($key);

            if ($catalog->has($key) && $value !== null && $value !== '') {
                $translated++;

                continue;
            }

            $missing[] = $key;
        }

        return new CompletenessReport($catalog->locale->code, count($sourceKeys), $translated, $missing);
    }

    private function isIgnored(string $key): bool
    {
        return $this->ignore !== [] && Str::is($this->ignore, $key);
    }
}
