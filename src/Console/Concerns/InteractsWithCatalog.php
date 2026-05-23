<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Concerns;

use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Storage\StorageManager;

trait InteractsWithCatalog
{
    protected function sourceLocale(): string
    {
        return (string) config('translations.locales.source', 'en');
    }

    protected function catalog(StorageManager $storage): Catalog
    {
        return $storage->driver()->readAll()->withSource($this->sourceLocale());
    }

    /**
     * @return list<string>
     */
    protected function extractionPaths(): array
    {
        return array_values((array) config('translations.extraction.paths', []));
    }
}
