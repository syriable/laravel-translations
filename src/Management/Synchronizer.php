<?php

declare(strict_types=1);

namespace Syriable\Translations\Management;

use Syriable\Translations\Contracts\TranslationDriver;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Extraction\ExtractionResult;

/**
 * Reconciles the keys discovered in source code with the stored catalog:
 * filling keys that are missing and, optionally, pruning keys that are no
 * longer used. Honours a dry-run mode so callers can preview changes.
 */
final class Synchronizer
{
    /**
     * @param  array{fill_missing?: bool, prune_unused?: bool, placeholder?: string|null}  $options
     */
    public function __construct(
        private readonly TranslationDriver $driver,
        private readonly string $sourceLocale,
        private readonly array $options = [],
    ) {}

    public function sync(ExtractionResult $extraction, bool $dryRun = false, ?string $onlyLocale = null): SyncReport
    {
        $fill = $this->options['fill_missing'] ?? true;
        $prune = $this->options['prune_unused'] ?? false;
        $placeholder = $this->options['placeholder'] ?? null;

        $usedKeys = $extraction->keyStrings();
        $report = new SyncReport($dryRun);

        $source = $this->driver->read(new Locale($this->sourceLocale));
        $this->reconcile($source, $usedKeys, $fill, $prune, $placeholder, $report);

        if (! $dryRun) {
            $this->driver->write($source);
        }

        $sourceKeys = $source->keys();

        foreach ($this->driver->locales() as $code) {
            if ($code === $this->sourceLocale || ($onlyLocale !== null && $code !== $onlyLocale)) {
                continue;
            }

            $target = $this->driver->read(new Locale($code));
            $this->reconcile($target, $sourceKeys, $fill, $prune, null, $report);

            if (! $dryRun) {
                $this->driver->write($target);
            }
        }

        return $report;
    }

    /**
     * @param  list<string>  $desiredKeys
     */
    private function reconcile(
        LocaleCatalog $catalog,
        array $desiredKeys,
        bool $fill,
        bool $prune,
        ?string $placeholder,
        SyncReport $report,
    ): void {
        $added = [];
        $pruned = [];

        if ($fill) {
            foreach ($desiredKeys as $key) {
                if (! $catalog->has($key)) {
                    $catalog->put($key, $placeholder);
                    $added[] = $key;
                }
            }
        }

        if ($prune) {
            $desired = array_flip($desiredKeys);

            foreach ($catalog->keys() as $key) {
                if (! isset($desired[$key])) {
                    $catalog->forget($key);
                    $pruned[] = $key;
                }
            }
        }

        $report->record($catalog->locale->code, $added, $pruned);
    }
}
