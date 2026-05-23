<?php

declare(strict_types=1);

namespace Syriable\Translations\Management;

use Syriable\Translations\Contracts\TranslationDriver;
use Syriable\Translations\Domain\Locale;

/**
 * Copies a catalog from one storage driver to another. Powers import (files to
 * the active driver) and export (active driver to files); when source and
 * destination are the same file driver it normalizes the catalog on disk.
 */
final class CatalogTransfer
{
    public function transfer(TranslationDriver $from, TranslationDriver $to, ?string $onlyLocale = null): TransferReport
    {
        $report = new TransferReport;

        foreach ($from->locales() as $code) {
            if ($onlyLocale !== null && $code !== $onlyLocale) {
                continue;
            }

            $catalog = $from->read(new Locale($code));
            $to->write($catalog);
            $report->record($code, $catalog->count());
        }

        return $report;
    }
}
